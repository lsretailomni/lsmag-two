<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Model\Order\Cancel as OrderCancel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;

/**
 * class to process status through webhook
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
     * @param CreditMemo $creditMemo
     * @param Notify $notify
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
     *
     * @param $data
     * @throws InvalidEnumException|NoSuchEntityException
     */
    public function process($data)
    {
        if (!empty($data)) {
            $documentId = $data['OrderId'];
            $lines      = $data['Lines'];
            $magOrder   = $this->helper->getOrderByDocumentId($documentId);
            if (!empty($magOrder)) {
                $itemInfoArray = $this->helper->mapStatusWithItemLines($lines);
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
     *
     * @param $status
     * @param $itemsInfo
     * @param $magOrder
     * @throws NoSuchEntityException
     */
    public function checkAndProcessStatus($status, $itemsInfo, $magOrder)
    {
        $storeId = $magOrder->getStoreId();
        $items   = $this->helper->getItems($magOrder, $itemsInfo);
        if (($status == LSR::LS_STATE_CANCELED || $status == LSR::LS_STATE_SHORTAGE)) {
            $this->cancel($magOrder, $itemsInfo, $items, $storeId);
        }
        if ($status == LSR::LS_STATE_PICKED && $this->helper->isPickupNotifyEnabled($storeId) &&
            $this->helper->isClickAndcollectOrder($magOrder)) {
            $templateId = $this->helper->getPickupTemplate($storeId);
            $this->processSendEmail($magOrder, $items, $templateId);
        }
        if ($status == LSR::LS_STATE_COLLECTED && $this->helper->isCollectedNotifyEnabled($storeId) &&
            $this->helper->isClickAndcollectOrder($magOrder)) {
            $templateId = $this->helper->getCollectedTemplate($storeId);
            $this->processSendEmail($magOrder, $items, $templateId);
        }
    }

    /**
     * Handling operation regarding cancelling the order
     *
     * @param $magOrder
     * @param $itemsInfo
     * @param $items
     * @param $storeId
     */
    public function cancel($magOrder, $itemsInfo, $items, $storeId)
    {
        $sendEmail = false;
        /** @var Order $magOrder */
        if ($magOrder->hasInvoices()) {
            $shippingItemId = $this->helper->getShippingItemId();
            $creditMemoData = $this->creditMemo->setCreditMemoParameters($magOrder, $itemsInfo, $shippingItemId);
            $this->creditMemo->refund($magOrder, $items, $creditMemoData);
        } elseif (count($magOrder->getAllVisibleItems()) == count($itemsInfo)) {
            $this->orderCancel->cancelOrder($magOrder->getEntityId());
            $sendEmail = true;
        } else {
            $this->orderCancel->cancelItems($magOrder, $items);
            $sendEmail = true;
        }

        if ($sendEmail && $this->helper->isCancelNotifyEnabled($storeId)) {
            $templateId = $this->helper->getCancelTemplate($storeId);
            $this->processSendEmail($magOrder, $items, $templateId);
        }
    }

    /** Process click and collect order
     *
     * @param $magOrder
     * @param $items
     * @param $templateId
     */
    public function processSendEmail($magOrder, $items, $templateId)
    {
        $templateVars = $this->notify->setTemplateVars($magOrder, $items);
        if (!empty($templateVars['items'])) {
            $this->notify->sendEmail($templateId, $templateVars, $magOrder);
        }
    }
}
