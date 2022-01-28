<?php

namespace Ls\Webhooks\Model\Order;

use Ls\Core\Model\LSR;
use Ls\Omni\Exception\InvalidEnumException;
use Ls\Webhooks\Helper\Data;
use Ls\Webhooks\Model\Order\Cancel as OrderCancel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;

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
     * @var Payment
     */
    private $payment;

    /**
     * Status constructor.
     * @param Data $helper
     * @param Cancel $orderCancel
     * @param CreditMemo $creditMemo
     * @param Notify $notify
     * @param Payment $payment
     */
    public function __construct(
        Data $helper,
        OrderCancel $orderCancel,
        CreditMemo $creditMemo,
        Notify $notify,
        Payment $payment
    ) {
        $this->helper      = $helper;
        $this->orderCancel = $orderCancel;
        $this->creditMemo  = $creditMemo;
        $this->notify      = $notify;
        $this->payment     = $payment;
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
                        $this->checkAndProcessStatus($status, $itemsInfo, $magOrder, $data);
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
     * @param $data
     * @throws NoSuchEntityException
     */
    public function checkAndProcessStatus($status, $itemsInfo, $magOrder, $data)
    {
        $storeId                = $magOrder->getStoreId();
        $items                  = $this->helper->getItems($magOrder, $itemsInfo);
        $isOffline              = $magOrder->getPayment()->getMethodInstance()->isOffline();
        $isClickAndCollectOrder = $this->helper->isClickAndcollectOrder($magOrder);

        if (($status == LSR::LS_STATE_CANCELED || $status == LSR::LS_STATE_SHORTAGE)) {
            if ($magOrder->canCancel()) {
                $cancelItems = [];

                foreach ($items as $itemId => $item) {
                    if ($item['itemStatus'] == Item::STATUS_PENDING ||
                        $item['itemStatus'] == Item::STATUS_PARTIAL) {
                        $cancelItems [] = $item;
                        unset($items[$itemId]);
                    }
                }
                $this->orderCancel->cancelItems($magOrder, $cancelItems);
            }

            if (!empty($items)) {
                $this->cancel($magOrder, $itemsInfo, $items, $storeId);
            }
        }

        if ($status == LSR::LS_STATE_PICKED && $isClickAndCollectOrder) {
            if ($this->helper->isPickupNotifyEnabled($storeId)) {
                $templateId = $this->helper->getPickupTemplate($storeId);
                $this->processSendEmail($magOrder, $items, $templateId);
            }
        }

        if ($status == LSR::LS_STATE_COLLECTED && $isClickAndCollectOrder) {
            if ($this->helper->isCollectedNotifyEnabled($storeId)) {
                $templateId = $this->helper->getCollectedTemplate($storeId);
                $this->processSendEmail($magOrder, $items, $templateId);
            }

            if ($isOffline) {
                $this->payment->generateInvoice($data);
            }
        }

        if ($status == LSR::LS_STATE_SHIPPED) {
            if ($isOffline) {
                $this->payment->generateInvoice($data);
            }
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
