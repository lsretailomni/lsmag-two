<?php

namespace Ls\Webhooks\Model\Order;

use Exception;
use \Ls\Webhooks\Logger\Logger;
use \Ls\Webhooks\Helper\Data;
use Magento\Framework\DB\TransactionFactory;
use Magento\InventoryInStorePickupSales\Model\Order\CreateShippingDocument;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

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
     * Payment constructor.
     * @param Logger $logger
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     * @param Data $helper
     */
    public function __construct(
        Logger $logger,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        InvoiceSender $invoiceSender,
        Data $helper,
        CreateShippingDocument $createShippingDocument
    ) {

        $this->logger             = $logger;
        $this->invoiceService     = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender      = $invoiceSender;
        $this->helper             = $helper;
        $this->createShippingDocument = $createShippingDocument;

    }

    /**
     * Generate invoice based on webhook call from Ls Central
     *
     * @param $data
     * @return array[]
     */
    public function generateInvoice($data)
    {
        $documentId     = $data['OrderId'];
        $lines          = $data['Lines'];
        $totalAmount    = 0;
        $shippingAmount = 0;
        $itemsToInvoice = [];
        try {
            $order           = $this->helper->getOrderByDocumentId($documentId);
            $validateOrder   = $this->validateOrder($order, $documentId);
            $validateInvoice = false;
            $invoice         = null;
            if ($validateOrder['data']['success'] && $order->canInvoice()) {
                $items = $this->helper->getItems($order, $lines);
                foreach ($items as $itemData) {
                    $item                         = $itemData['item'];
                    $orderItemId                  = $item->getItemId();
                    $itemsToInvoice[$orderItemId] = $itemData['qty'];
                    $totalAmount                  += $itemData['amount'];
                }

                foreach ($lines as $line) {
                    if ($line['ItemId'] == $this->helper->getShippingItemId()) {
                        $shippingAmount = $line['Amount'];
                        $totalAmount    += $shippingAmount;
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

                    if($order->getShippingMethod() == "clickandcollect_clickandcollect") {
                        $this->createShippingDocument->execute($order);
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
}
