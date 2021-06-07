<?php

namespace Ls\Webhooks\Model\Order;

use Exception;
use Ls\Core\Model\LSR;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Model\Order\Cancel as OrderCancel;
use \Ls\Webhooks\Model\Order\CreditMemo;
use \Ls\Webhooks\Model\Order\Notify;
use Magento\Sales\Model\Order;

/**
 * class to create invoice through webhook
 */
class Status
{

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var OrderCancel
     */
    private $orderCancel;

    /**
     * @var CreditMemo
     */
    private $creditMemo;

    /**
     * @var Notify
     */
    private $notify;

    /**
     * Status constructor.
     * @param Data $helper
     * @param Cancel $orderCancel
     * @param \Ls\Webhooks\Model\Order\CreditMemo $creditMemo
     * @param \Ls\Webhooks\Model\Order\Notify $notify
     */
    public function __construct(
        Data $helper,
        OrderCancel $orderCancel,
        CreditMemo $creditMemo,
        Notify $notify
    ) {
        $this->helper      = $helper;
        $this->orderCancel = $orderCancel;
        $this->creditMemo  = $creditMemo;
        $this->notify      = $notify;
    }

    /**
     * Process order status based on webhook call from Ls Central
     * @param $data
     */
    public function process($data)
    {
        if (!empty($data)) {
            $documentId = $data['orderId'];
            $lines      = $data['lines'];
            $magOrder   = $this->helper->getOrderByDocumentId($documentId);
            if (!empty($magOrder)) {
                $salesEntry    = $this->helper->getSalesEntry($documentId);
                $itemInfoArray = $this->helper->matchLineNumberWithSalesEntry($salesEntry, $lines);
                if (!empty($itemInfoArray)) {
                    foreach ($itemInfoArray as $status => $itemsInfo) {
                        $this->checkAndProcessStatus($status, $itemsInfo, $magOrder);
                    }
                }
            }
        }
    }

    /**
     * Check and process order status
     * @param $status
     * @param $itemsInfo
     * @param $magOrder
     * @throws Exception
     */
    public function checkAndProcessStatus($status, $itemsInfo, $magOrder)
    {
        $storeId = $magOrder->getStoreId();
        if (($status == LSR::LS_STATE_CANCELED || $status == LSR::LS_STATE_SHORTAGE)) {
            $this->cancel($magOrder, $itemsInfo);
        }
        if ($status == LSR::LS_STATE_PICKED && $this->helper->isPickupNotifyEnabled($storeId)) {
            $templateId   = $this->helper->getPickupTemplate($storeId);
            $templateVars = $this->notify->setClickAndCollectTemplateVars($magOrder, $itemsInfo);
            $this->notify->sendEmail($templateId, $templateVars, $magOrder);
        }
        if ($status == LSR::LS_STATE_COLLECTED && $this->helper->isCollectedNotifyEnabled($storeId)) {
            $templateId   = $this->helper->getCollectedTemplate($storeId);
            $templateVars = $this->notify->setClickAndCollectTemplateVars($magOrder, $itemsInfo);
            $this->notify->sendEmail($templateId, $templateVars, $magOrder);
        }
    }

    /**
     * Handling operation regarding cancelling the order
     * @param $magOrder
     * @param $itemsInfo
     * @throws Exception
     */
    public function cancel($magOrder, $itemsInfo)
    {
        /** @var Order $magOrder */
        if ($magOrder->hasInvoices()) {
            $creditMemoData = $this->creditMemo->setCreditMemoParameters($magOrder);
            $this->creditMemo->refund($magOrder, $itemsInfo, $creditMemoData);
        } elseif (count($magOrder->getAllVisibleItems()) == count($itemsInfo)) {
            $this->orderCancel->cancelOrder($magOrder->getEntityId());
        }
    }
}
