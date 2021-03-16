<?php

namespace Ls\Webhooks\Model;

use Exception;
use \Ls\Webhooks\Api\OrderPaymentInterface;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * Class for handling order payment and invoice
 */
class OrderPayment implements OrderPaymentInterface
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
     * @var Data
     */
    public $helper;

    /**
     * OrderPayment constructor.
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Data $helper
     */
    public function __construct(
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        InvoiceSender $invoiceSender,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Data $helper
    ) {

        $this->logger                = $logger;
        $this->orderRepository       = $orderRepository;
        $this->invoiceService        = $invoiceService;
        $this->transactionFactory    = $transactionFactory;
        $this->invoiceSender         = $invoiceSender;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper                = $helper;
    }

    /**
     * @inheritdoc
     */
    public function set($documentId, $status, $token, $amount)
    {
        try {
            $data = [
                'documentId' => $documentId,
                'status'     => $status,
                'token'      => $token,
                'amount'     => $amount
            ];
            $this->logger->info('orderpayment', $data);
            if (!empty($documentId)) {
                return $this->helper->generateInvoice($data);
            }
            return [
                "data" => [
                    'success' => false,
                    'message' => 'Document Id is not valid.'
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
}
