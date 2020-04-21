<?php

namespace Ls\WebHooks\Model;

use Exception;
use \Ls\Webhooks\Api\OrderPaymentInterface;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * Class OrderPayment
 * @package Ls\WebHooks\Api\Model
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

    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
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
     * @param ManagerInterface $messageManager
     * @param InvoiceSender $invoiceSender
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Data $helper
     */
    public function __construct(
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        ManagerInterface $messageManager,
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
     * set order status Api.
     *
     * @param string $document_id
     * @param string $status (Unchanged=0,Changed=1,Cancelled=2)
     * @param string $token
     * @param double $amount
     *
     * @return String
     * @api
     *
     */
    public function set($documentId, $status, $token, $amount)
    {
        try {
            $data = [
                'document_id' => $documentId,
                'status'      => $status,
                'token'       => $token,
                'amount'      => $amount
            ];
            $this->logger->info('orderpayment', $data);
            if (!empty($documentId)) {
                $result = $this->helper->generateInvoice($data);
                return $result;
            }
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return $this->helper::ERROR;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->helper::ERROR;
        }
    }
}
