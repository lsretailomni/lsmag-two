<?php

namespace Ls\Webhooks\Model\Order;

use Exception;
use \Ls\Webhooks\Logger\Logger;
use \Ls\Webhooks\Helper\Data;
use Magento\Framework\DB\TransactionFactory;
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
        Data $helper
    ) {

        $this->logger             = $logger;
        $this->invoiceService     = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender      = $invoiceSender;
        $this->helper             = $helper;
    }

    /**
     * Generate invoice based on webhook call from Ls Central
     * @param $data
     * @return array[]
     */
    public function generateInvoice($data)
    {
        $documentId = $data['documentId'];
        $amount     = $data['amount'];
        $token      = $data['token'];
        try {
            $order           = $this->helper->getOrderByDocumentId($documentId);
            $validateOrder   = $this->validateOrder($order, $amount, $documentId, $token);
            $validateInvoice = false;
            $invoice         = null;
            if ($validateOrder['data']['success']) {
                $invoice         = $this->invoiceService->prepareInvoice($order);
                $validateInvoice = $this->validateInvoice($invoice, $documentId);
            }
            if ($validateInvoice) {
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->getOrder()->setCustomerNoteNotify(false);
                $invoice->getOrder()->setIsInProcess(true);
                $invoice->setGrandTotal($amount);
                $invoice->setBaseGrandTotal($amount);
                $invoice->getOrder()->setTotalPaid($amount);
                $invoice->getOrder()->setBaseTotalPaid($amount);
                $order->addCommentToStatusHistory('INVOICED FROM LS CENTRAL THROUGH WEBHOOK', false);
                $transactionSave = $this->transactionFactory->create()->addObject($invoice)->
                addObject($invoice->getOrder());
                $transactionSave->save();
                try {
                    $this->invoiceSender->send($invoice);
                    return [
                        "data" => [
                            'success' => true,
                            'message' => 'Order posted successfully and invoice sent to customer for document id #'
                                . $documentId
                        ]
                    ];
                } catch (Exception $e) {
                    $this->logger->error('We can\'t send the invoice email right now for document id #' . $documentId);
                    return [
                        "data" => [
                            'success' => false,
                            'message' => "We can\'t send the invoice email right now for document id #" . $documentId
                        ]
                    ];
                }
            }

            $hasInvoices = $this->hasInvoices($order, $documentId);
            if ($hasInvoices['data']['success']) {
                return $hasInvoices;
            }
            return $validateOrder;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return [
                "data" => [
                    'success' => false,
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * validate order
     * @param $order
     * @param $amount
     * @param $documentId
     * @param $token
     * @return array[]
     */
    public function validateOrder($order, $amount, $documentId, $token)
    {
        $validate = true;
        $message  = '';
        if (!$order->getId() || $order->getPayment()->getLastTransId() != $token) {
            $message = "The order does not exist or token does not match for document id #" . $documentId;
            $this->logger->error($message);
            $validate = false;
        }

        if ($order->getGrandTotal() < $amount) {
            $message = "Invoice amount is greater than order amount for document id #" . $documentId;
            $this->logger->error($message);
            $validate = false;
        }

        return [
            "data" => [
                'success' => $validate,
                'message' => $message
            ]
        ];
    }

    /**
     * check if invoice is already created at magento end
     * @param $order
     * @param $documentId
     * @return array[]
     */
    public function hasInvoices($order, $documentId)
    {
        $validate = false;
        $message  = '';
        if ($order->hasInvoices()) {
            $message = "Invoice already created for document id #" . $documentId;
            $this->logger->info($message);
            $validate = true;
        }

        return [
            "data" => [
                'success' => $validate,
                'message' => $message
            ]
        ];
    }

    /**
     * validate invoice
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
