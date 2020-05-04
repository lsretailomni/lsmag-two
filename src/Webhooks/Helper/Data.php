<?php

namespace Ls\WebHooks\Helper;

use Exception;
use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * Class Data
 * @package Ls\WebHooks\Helper
 */
class Data
{
    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var InvoiceService
     */
    public $invoiceService;

    /**
     * @var TransactionFactory
     */
    public $transactionFactory;

    /**
     * @var InvoiceSender
     */
    public $invoiceSender;

    /**
     * @var SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * Data constructor.
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        InvoiceSender $invoiceSender,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {

        $this->logger                = $logger;
        $this->orderRepository       = $orderRepository;
        $this->invoiceService        = $invoiceService;
        $this->transactionFactory    = $transactionFactory;
        $this->invoiceSender         = $invoiceSender;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param $data
     * @return array
     */
    public function generateInvoice($data)
    {
        $documentId = $data['documentId'];
        $amount     = $data['amount'];
        $token      = $data['token'];
        try {
            $order           = $this->getOrderByDocumentId($documentId);
            $validateOrder   = $this->validateOrder($order, $amount, $documentId, $token);
            $validateInvoice = false;
            $invoice         = null;
            if ($validateOrder) {
                $invoice         = $this->invoiceService->prepareInvoice($order);
                $validateInvoice = $this->validateInvoice($invoice, $documentId);
            } else {
                return [
                    "data" => [
                        'success' => false,
                        'message' => 'Validate order failed at Magento end.'
                    ]
                ];
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
                            'message' => 'Order posted successfully and invoice sent to customer for document id #' . $documentId
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
            return [
                "data" => [
                    'success' => false,
                    'message' => 'Validate invoice failed at Magento end.'
                ]
            ];
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
     * @param $documentId
     * @return array|OrderInterface|OrderInterface[]
     */
    public function getOrderByDocumentId($documentId)
    {
        try {
            $order = [];
            $order = $this->orderRepository->getList(
                $this->searchCriteriaBuilder->addFilter('document_id', $documentId, 'eq')->create()
            )->getItems();
            foreach ($order as $ord) {
                return $ord;
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $order;
    }

    /**
     * @param $order
     * @param $amount
     * @param $documentId
     * @param $token
     * @return bool
     */
    public function validateOrder($order, $amount, $documentId, $token)
    {
        $validate = true;
        if (!$order->getId() || $order->getPayment()->getLastTransId() != $token) {
            $this->logger->error(
                'The order does not exist or token does not match for document id #' . $documentId
            );
            $validate = false;
        }
        if ($order->hasInvoices()) {
            $this->logger->error(
                'Invoice already created for document id #' . $documentId
            );
            $validate = false;
        }
        if ($order->getGrandTotal() < $amount) {
            $this->logger->error(
                'Invoice amount is greater than order amount for document id #' . $documentId
            );
            $validate = false;
        }
        return $validate;
    }

    /**
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
