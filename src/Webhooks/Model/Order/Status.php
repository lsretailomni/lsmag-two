<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Core\Model\LSR;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Model\Notification\EmailNotification;
use \Ls\Webhooks\Model\Order\Cancel as OrderCancel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

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
     * @var Payment
     */
    private $payment;

    /**
     * @var EmailNotification
     */
    private $emailNotification;

    /**
     * @param Data $helper
     * @param Cancel $orderCancel
     * @param CreditMemo $creditMemo
     * @param Payment $payment
     * @param EmailNotification $emailNotification
     */
    public function __construct(
        Data $helper,
        OrderCancel $orderCancel,
        CreditMemo $creditMemo,
        Payment $payment,
        EmailNotification $emailNotification
    ) {
        $this->helper      = $helper;
        $this->orderCancel = $orderCancel;
        $this->creditMemo  = $creditMemo;
        $this->payment     = $payment;
        $this->emailNotification = $emailNotification;
    }

    /**
     * Process order status based on webhook call from Ls Central
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
     * @param string $status
     * @param array $itemsInfo
     * @param OrderInterface $magOrder
     * @param array $data
     * @throws NoSuchEntityException|LocalizedException
     */
    public function checkAndProcessStatus($status, $itemsInfo, $magOrder, $data)
    {
        $items                      = $this->helper->getItems($magOrder, $itemsInfo);
        $isOffline                  = $magOrder->getPayment()->getMethodInstance()->isOffline();
        $isClickAndCollectOrder     = $this->helper->isClickAndcollectOrder($magOrder);
        $storeId                    = $magOrder->getStoreId();
        $configuredNotificationType = explode(',', $this->helper->getNotificationType($storeId));
        $orderStatus                = null;

        if (($status == LSR::LS_STATE_CANCELED || $status == LSR::LS_STATE_SHORTAGE)) {
            $this->cancel($magOrder, $itemsInfo, $items);
            $orderStatus = LSR::LS_STATE_CANCELED;
        }

        if ($status == LSR::LS_STATE_PICKED && $isClickAndCollectOrder) {
            $orderStatus = LSR::LS_STATE_PICKED;
        }

        if ($status == LSR::LS_STATE_COLLECTED && $isClickAndCollectOrder) {
            $orderStatus = LSR::LS_STATE_COLLECTED;

            if ($isOffline) {
                $this->payment->generateInvoice($data);
            }
        }

        if ($status == LSR::LS_STATE_SHIPPED) {
            if ($isOffline) {
                $this->payment->generateInvoice($data);
            }
        }

        if ($orderStatus !== null) {
            foreach ($configuredNotificationType as $type) {
                if ($type == LSR::LS_NOTIFICATION_EMAIL) {
                    $this->emailNotification->setNotificationType($orderStatus);
                    $this->emailNotification->setOrder($magOrder)->setItems($items);
                    $this->emailNotification->prepareAndSendNotification();
                }
            }
        }
    }

    /**
     * Handling operation regarding cancelling the order
     *
     * @param OrderInterface $magOrder
     * @param array $itemsInfo
     * @param array $items
     */
    public function cancel($magOrder, $itemsInfo, $items)
    {
        $isClickAndCollectOrder = $this->helper->isClickAndcollectOrder($magOrder);
        $magentoOrderTotalItemsQty = (int) $magOrder->getTotalQtyOrdered();
        $shipmentLineCount = (int) $isClickAndCollectOrder ? 0 : 1;
        $magentoOrderTotalItemsQty = $magentoOrderTotalItemsQty + $shipmentLineCount;

        if ($magentoOrderTotalItemsQty == count($itemsInfo)) {
            $this->orderCancel->cancelOrder($magOrder->getEntityId());
        } else {
            $this->orderCancel->cancelItems($magOrder, $items);
        }

        if ($magOrder->hasInvoices()) {
            $shippingItemId = $this->helper->getShippingItemId();
            $creditMemoData = $this->creditMemo->setCreditMemoParameters($magOrder, $itemsInfo, $shippingItemId);
            $this->creditMemo->refund($magOrder, $items, $creditMemoData);
        }
    }
}
