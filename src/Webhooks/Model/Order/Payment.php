<?php

namespace Ls\Webhooks\Model\Order;

use Exception;
use \Ls\Webhooks\Logger\Logger;
use \Ls\Webhooks\Helper\Data;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Shipping\Model\ShipmentNotifier;

/**
 * class to create invoice through webhook
 */
class Payment
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Order
     */
    private $convertOrder;

    /**
     * @var ShipmentNotifier
     */
    private $shipmentNotifier;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @param Logger $logger
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     * @param Data $helper ,
     * @param OrderRepositoryInterface $orderRepository
     * @param Order $convertOrder
     * @param ShipmentNotifier $shipmentNotifier
     * @param ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(
        Logger $logger,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        InvoiceSender $invoiceSender,
        Data $helper,
        OrderRepositoryInterface $orderRepository,
        Order $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        ShipmentRepositoryInterface $shipmentRepository
    ) {
        $this->logger             = $logger;
        $this->invoiceService     = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender      = $invoiceSender;
        $this->helper             = $helper;
        $this->orderRepository    = $orderRepository;
        $this->convertOrder       = $convertOrder;
        $this->shipmentNotifier   = $shipmentNotifier;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * Generate invoice based on webhook call from Ls Central
     *
     * @param $data
     * @return array[]
     */
    public function generateInvoice($data)
    {
        $documentId = $data['OrderId'];
        $lines      = $data['Lines'];
        if (array_key_exists('Amount', $data)) {
            $totalAmount = $data['Amount'];
        } else {
            $totalAmount = 0;
        }
        $shippingAmount = 0;
        $itemsToInvoice = [];
        try {
            $order           = $this->helper->getOrderByDocumentId($documentId);
            $isOffline       = $order->getPayment()->getMethodInstance()->isOffline();
            $validateOrder   = $this->validateOrder($order, $documentId);
            $validateInvoice = false;
            $invoice         = null;
            if ($validateOrder['data']['success'] && $order->canInvoice()) {
                $items = $this->helper->getItems($order, $lines);
                foreach ($items as $itemData) {
                    $item                         = $itemData['item'];
                    $orderItemId                  = $item->getItemId();
                    $itemsToInvoice[$orderItemId] = $itemData['qty'];
                    if ($isOffline) {
                        $totalAmount += $itemData['amount'];
                    }
                }

                foreach ($lines as $line) {
                    if ($line['ItemId'] == $this->helper->getShippingItemId()) {
                        $shippingAmount = $line['Amount'];
                        if ($isOffline) {
                            $totalAmount += $shippingAmount;
                        }
                    }
                }
                if ($isOffline && !$order->hasInvoices()) {
                    if ($order->getLsGiftCardAmountUsed() > 0) {
                        $totalAmount = $totalAmount - $order->getLsGiftCardAmountUsed();
                    }
                    if ($order->getLsPointsSpent() > 0) {
                        $totalAmount = $totalAmount - ($order->getLsPointsSpent() * $this->helper->getPointRate());
                    }
                }

                $validateOrder   = $this->validatePayment($order, $totalAmount, $documentId, $shippingAmount);
                $invoice         = $this->invoiceService->prepareInvoice($order, $itemsToInvoice);
                $validateInvoice = $this->validateInvoice($invoice, $documentId);
            }
            if ($validateInvoice && $validateOrder['data']['success']) {
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                $invoice->getOrder()->setCustomerNoteNotify(false);
                $invoice->getOrder()->setIsInProcess(true);
                $invoice->setShippingAmount($shippingAmount);
                $invoice->setSubtotal($totalAmount);
                $invoice->setBaseSubtotal($totalAmount);
                $invoice->setGrandTotal($totalAmount);
                $invoice->setBaseGrandTotal($totalAmount);
                $invoice->register();
                $order->addCommentToStatusHistory('INVOICED FROM LS CENTRAL THROUGH WEBHOOK', false);
                $transactionSave = $this->transactionFactory->create()->addObject($invoice)->
                addObject($invoice->getOrder());
                $transactionSave->save();
                try {
                    $this->invoiceSender->send($invoice);

                    if ($order->getShippingMethod() == "clickandcollect_clickandcollect") {
                        $this->createShipment($order, $lines);
                    }

                    return $this->helper->outputMessage(
                        true,
                        'Order posted successfully and invoice sent to customer for document id #' . $documentId
                    );
                } catch (Exception $e) {
                    $this->logger->error('We can\'t send the invoice email right now for document id #'
                        . $documentId);

                    return $this->helper->outputMessage(
                        false,
                        "We can\'t send the invoice email right now for document id #" . $documentId
                    );
                }
            }

            return $validateOrder;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());

            return $this->helper->outputMessage(
                false,
                $e->getMessage()
            );
        }
    }

    /**
     * Validate order
     *
     * @param $order
     * @param $documentId
     * @return array[]
     */
    public function validateOrder($order, $documentId)
    {
        $validate = true;
        $message  = '';
        if (empty($order)) {
            $message = "Order does not exist for document id #" . $documentId;
            $this->logger->error($message);
            $validate = false;
        }

        return $this->helper->outputMessage($validate, $message);
    }

    /**
     * Validate total amount
     *
     * @param $order
     * @param $amount
     * @param $documentId
     * @param $shippingAmount
     * @return array[]
     */
    public function validatePayment($order, $amount, $documentId, $shippingAmount)
    {
        $validate = true;
        $message  = '';

        if ($order->getGrandTotal() < $amount && $order->getTotalDue() <= $amount) {
            $message = "Invoice amount is greater than order amount for document id #" . $documentId;
            $this->logger->error($message);
            $validate = false;
        }

        return $this->helper->outputMessage($validate, $message);
    }

    /**
     * validate invoice
     *
     * @param $invoice
     * @param $documentId
     * @return bool
     */
    public function validateInvoice($invoice, $documentId)
    {
        $validate = true;
        if (!$invoice || !$invoice->getTotalQty()) {
            $this->logger->error(
                'We can\'t save the invoice right now for document id #' . $documentId
            );
            $validate = false;
        }
        return $validate;
    }

    /**
     * Create shipment
     *
     * @param $order
     * @param $lines
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createShipment($order, $lines)
    {
        if (!$order->canShip()) {
            throw new LocalizedException(
                __('You can\'t create the Shipment of this order.')
            );
        }
        $orderShipment = $this->convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            $parentItem = $orderItem->getParentItem();

            if (!$this->isAllowed($orderItem, $lines) ||
                ($parentItem ?
                    !$parentItem->getQtyToShip() : !$orderItem->getQtyToShip())
            ) {
                continue;
            }
            if (empty($parentItem)) {
                $parentItem = $orderItem;
            }
            $qty          = $this->getQtyToShip($orderItem, $lines);
            $shipmentItem = $this->convertOrder->itemToShipmentItem($parentItem)->setQty($qty);
            $orderShipment->addItem($shipmentItem);
        }
        $orderShipment->register();
        $order->setIsInProcess(true);
        try {
            // Save created Order Shipment
            $this->shipmentRepository->save($orderShipment);
            $this->orderRepository->save($order);
            // Send Shipment Email
            $this->shipmentNotifier->notify($orderShipment);
        } catch (\Exception $e) {
            throw new LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Is allowed
     *
     * @param $orderItem
     * @param $lines
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isAllowed($orderItem, $lines)
    {
        $product = $this->helper->getProductById($orderItem->getProductId());
        $found   = false;

        foreach ($lines as $line) {
            $itemId    = $line['ItemId'];
            $variantId = $line['VariantId'];

            if ($product->getLsrItemId() == $itemId && $product->getLsrVariantId() == $variantId) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * Get qty to ship
     *
     * @param $orderItem
     * @param $lines
     * @return int
     * @throws NoSuchEntityException
     */
    public function getQtyToShip($orderItem, $lines)
    {
        $product = $this->helper->getProductById($orderItem->getProductId());
        $qty     = 0;

        foreach ($lines as $line) {
            $itemId    = $line['ItemId'];
            $variantId = $line['VariantId'];

            if ($product->getLsrItemId() == $itemId && $product->getLsrVariantId() == $variantId) {
                $qty++;
            }
        }

        return $qty;
    }
}
