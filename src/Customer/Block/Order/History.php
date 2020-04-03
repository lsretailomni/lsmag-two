<?php

namespace Ls\Customer\Block\Order;

use DateTime;
use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntry;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Class History
 * @package Ls\Customer\Block\Order
 */
class History extends \Magento\Sales\Block\Order\History
{
    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Order Repository
     */
    public $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * History constructor.
     * @param Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param Proxy $customerSession
     * @param Config $orderConfig
     * @param OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param LSR $LSR
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $orderCollectionFactory,
        Proxy $customerSession,
        Config $orderConfig,
        OrderHelper $orderHelper,
        PriceCurrencyInterface $priceCurrency,
        LSR $LSR,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->orderHelper           = $orderHelper;
        $this->priceCurrency         = $priceCurrency;
        $this->lsr                   = $LSR;
        $this->orderRepository       = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }

    /**
     * @return array|bool|ArrayOfSalesEntry|Collection|null
     */
    public function getOrderHistory()
    {
        /*
        * Adding condition to only process if LSR is enabled.
        */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $response = [];
            $orders   = $this->orderHelper->getCurrentCustomerOrderHistory();
            if ($orders) {
                try {
                    $response = $orders;
                } catch (Exception $e) {
                    $this->_logger->error($e->getMessage());
                }
            }
            return $response;
        }
        return parent::getOrders();
    }

    /**
     * @param $amount
     * @return float
     */
    public function getFormattedPrice($amount)
    {
        $price = $this->priceCurrency->format($amount, false, 2);
        return $price;
    }

    /**
     * @param $date
     * @return string
     * @throws Exception
     */
    public function getFormattedDate($date)
    {
        // @codingStandardsIgnoreStart
        $formattedDate = new DateTime($date);
        // @codingStandardsIgnoreEnd
        $result = $formattedDate->format('d/m/y');
        return $result;
    }

    /**
     * @param object $order
     * @param null $magOrder
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getViewUrl($order, $magOrder = null)
    {
        /*
        * Adding condition to only process if LSR is enabled.
        */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if (version_compare($this->lsr->getOmniVersion(), '4.5.0', '==')) {
                // This condition is added to support viewing of orders created by POS
                if (!empty($magOrder)) {
                    return $this->getUrl(
                        'customer/order/view',
                        [
                            'order_id' => $order->getId()
                        ]
                    );
                }
            }

            return $this->getUrl(
                'customer/order/view',
                [
                    'order_id' => $order->getId(),
                    'type'     => $order->getIdType()
                ]
            );
        }
        return parent::getViewUrl($order);
    }

    /**
     * @param object $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        try {
            if ($order->getDocumentId() != null) {
                return $this->getUrl('sales/order/reorder', ['order_id' => $order->getEntityId()]);
            } else {
                return parent::getReorderUrl($order);
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $documentId
     * @return OrderInterface[]
     */
    public function getOrderByDocumentId($documentId)
    {
        return $this->orderHelper->getOrderByDocumentId($documentId);
    }

    /**
     * @param object $invoice
     * @return string
     */
    public function getPrintInvoiceUrl($invoice)
    {
        return $this->getUrl('*/*/printInvoice', ['invoice_id' => $invoice->getId()]);
    }

    /**
     * @param object $order
     * @return string
     */
    public function getPrintAllInvoicesUrl($order)
    {
        return $this->getUrl('*/*/printInvoice', ['order_id' => $order->getDocumentId()]);
    }

    /**
     * @return string
     */
    public function getOmniVersion()
    {
        return $this->lsr->getOmniVersion();
    }
}
