<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Core\Model\LSR;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Helper\NotificationHelper;
use \Ls\Webhooks\Model\Order\Cancel as OrderCancel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\CreditmemoService;

/**
 * class to process status through webhook
 */
class Status
{
    public const SUCCESS_MESSAGE = 'success';

    /**
     * @var Data
     */
    public $helper;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var OrderCancel
     */
    public $orderCancel;

    /**
     * @var CreditMemo
     */
    public $creditMemo;

    /**
     * @var Payment
     */
    public $payment;

    /**
     * @var Invoice
     */
    public $invoice;

    /**
     * @var CreditmemoFactory
     */
    public $creditMemoFactory;

    /**
     * @var CreditmemoService
     */
    public $creditMemoService;

    /**
     * @var array
     */
    public $message = [];

    /**
     * @param Data $helper
     * @param Cancel $orderCancel
     * @param CreditMemo $creditMemo
     * @param Payment $payment
     * @param Invoice $invoice
     * @param NotificationHelper $notificationHelper
     * @param CreditmemoFactory $creditMemoFactory
     * @param CreditmemoService $creditMemoService
     */
    public function __construct(
        Data $helper,
        OrderCancel $orderCancel,
        CreditMemo $creditMemo,
        Payment $payment,
        Invoice $invoice,
        NotificationHelper $notificationHelper,
        CreditmemoFactory $creditMemoFactory,
        CreditmemoService $creditMemoService
    ) {
        $this->helper                 = $helper;
        $this->orderCancel            = $orderCancel;
        $this->creditMemo             = $creditMemo;
        $this->payment                = $payment;
        $this->invoice                = $invoice;
        $this->notificationHelper     = $notificationHelper;
        $this->creditMemoFactory      = $creditMemoFactory;
        $this->creditMemoService      = $creditMemoService;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Process order status based on webhook call from Ls Central
     *
     * @param $data
     * @return array
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

        return $this->message;
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
        $items                  = $this->helper->getItems($magOrder, $itemsInfo, false);
        $isOffline              = $magOrder->getPayment()->getMethodInstance()->isOffline();
        $isClickAndCollectOrder = $this->helper->isClickAndcollectOrder($magOrder);
        $storeId                = $magOrder->getStoreId();
        $orderStatus            = null;
        $message = __("Your order has been");
        switch ($status) {
            case LSR::LS_STATE_CANCELED:
            case LSR::LS_STATE_SHORTAGE:
                $this->cancel($magOrder, $itemsInfo, $items);
                $orderStatus = LSR::LS_STATE_CANCELED;
                break;
            case LSR::LS_STATE_PICKED:
                if ($isClickAndCollectOrder) {
                    $orderStatus = LSR::LS_STATE_PICKED;
                }
                $orderStatus = '';
                $message = __("Your order is ready for PICKUP");
                break;
            case LSR::LS_STATE_COLLECTED:
                if ($isClickAndCollectOrder) {
                    $orderStatus = LSR::LS_STATE_COLLECTED;
                    if ($isOffline) {
                        $this->payment->generateInvoice($data, false);
                    }
                }
                break;
            case LSR::LS_STATE_SHIPPED:
                if ($isOffline) {
                    $this->payment->generateInvoice($data, false);
                }
                break;
            default:
                $orderStatus = $status;
                break;
        }

        if ($orderStatus !== null) {
            $this->notificationHelper->processNotifications(
                $storeId,
                $magOrder,
                $items,
                $message.' '.$orderStatus
            );
        }
    }

    /**
     * Handling operation regarding cancelling the order
     *
     * @param OrderInterface $magOrder
     * @param array $itemsInfo
     * @param array $items
     * @throws LocalizedException
     */
    public function cancel($magOrder, $itemsInfo, $items)
    {
        $isClickAndCollectOrder    = $this->helper->isClickAndcollectOrder($magOrder);
        $magentoOrderTotalItemsQty = (int)$magOrder->getTotalQtyOrdered();
        $shipmentLineCount         = (int)$isClickAndCollectOrder ? 0 : 1;
        $magentoOrderTotalItemsQty = $magentoOrderTotalItemsQty +
            (($shipmentLineCount == 1 && $magOrder->getShippingAmount()) ? $shipmentLineCount : 0);

        if ($magentoOrderTotalItemsQty == count($itemsInfo)) {
            $this->message = $this->orderCancel->cancelOrder($magOrder->getEntityId());
        } else {
            $this->message = $this->orderCancel->cancelItems($magOrder, $items);
        }

        if ($magOrder->hasInvoices() && $this->itemExistsInInvoice($magOrder, $itemsInfo)) {
            if ($magentoOrderTotalItemsQty == count($itemsInfo)) {
                $invoices = $magOrder->getInvoiceCollection();

                foreach ($invoices as $invoice) {
                    $invoiceIncrementId = $invoice->getIncrementId();
                    $invoiceObj         = $this->invoice->loadByIncrementId($invoiceIncrementId);
                    $creditMemo         = $this->creditMemoFactory->createByOrder($magOrder);
                    $creditMemo->setInvoice($invoiceObj);
                    $this->creditMemoService->refund($creditMemo);
                }
            } else {
                $invoice = null;

                foreach ($itemsInfo as $item) {
                    $itemId    = $item['ItemId'];
                    $variantId = $item['VariantId'];
                    $invoice   = $this->getItemInvoice($magOrder, $itemId, $variantId);
                }
                $shippingItemId = $this->helper->getShippingItemId();
                $creditMemoData = $this->creditMemo->setCreditMemoParameters($magOrder, $itemsInfo, $shippingItemId);
                $this->message  = $this->creditMemo->refund($magOrder, $items, $creditMemoData, $invoice);
            }
            $message       = Status::SUCCESS_MESSAGE;
            $this->message = $this->helper->outputMessage(true, $message);
        }
    }

    /**
     * Item exists in invoice
     *
     * @param $magOrder
     * @param $itemsInfo
     * @return bool
     * @throws NoSuchEntityException
     */
    public function itemExistsInInvoice($magOrder, $itemsInfo)
    {
        $exists1 = true;

        foreach ($itemsInfo as $item) {
            $itemId    = $item['ItemId'];
            $variantId = $item['VariantId'];
            $exists2   = $this->getItemInvoice($magOrder, $itemId, $variantId);
            $exists1   = $exists1 && $exists2;
        }

        return $exists1;
    }

    /**
     * Get item invoice
     *
     * @param $magOrder
     * @param $itemId
     * @param $variantId
     * @return false|Invoice
     * @throws NoSuchEntityException
     */
    public function getItemInvoice($magOrder, $itemId, $variantId)
    {
        $invoices        = $magOrder->getInvoiceCollection();
        $requiredInvoice = false;

        foreach ($invoices as $invoice) {
            $invoiceIncrementId = $invoice->getIncrementId();
            $invoiceObj         = $this->invoice->loadByIncrementId($invoiceIncrementId);

            foreach ($invoiceObj->getItems() as $invoiceItem) {
                $product = $this->helper->getProductById($invoiceItem->getProductId());

                if ($product->getLsrItemId() == $itemId && $product->getLsrVariantId() == $variantId) {
                    $requiredInvoice = $invoiceObj;
                    break;
                }
            }
        }

        return $requiredInvoice;
    }

    /**
     * Get Helper Object
     *
     * @return Data
     */
    public function getHelperObject()
    {
        return $this->helper;
    }
}
