<?php

namespace Ls\Customer\Block\Order;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntriesGetByCardIdResponse;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Block being used for order history grid
 */
class History extends \Magento\Sales\Block\Order\History
{
    /**
     *  History constructor.
     * 
     * @param Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param CustomerSession $customerSession
     * @param Config $orderConfig
     * @param OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param LSR $LSR
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        public Context $context,
        public CollectionFactory $orderCollectionFactory,
        public CustomerSession $customerSession,
        public Config $orderConfig,
        public OrderHelper $orderHelper,
        public PriceCurrencyInterface $priceCurrency,
        public LSR $lsr,
        public OrderRepository $orderRepository,
        public SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }

    /**
     * Get order history
     *
     * @return array|bool|ArrayOfSalesEntry|SalesEntriesGetByCardIdResponse|ResponseInterface|Collection
     * @throws NoSuchEntityException
     */
    public function getOrderHistory()
    {
        /*
        * Adding condition to only process if LSR is enabled.
        */
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getCustomerIntegrationOnFrontend()
        )) {
            $response = [];
            $orders   = $this->orderHelper->getCurrentCustomerOrderHistory();
            if ($orders) {
                try {
                    $response = $this->orderHelper->processOrderData($orders);
                } catch (Exception $e) {
                    $this->_logger->error($e->getMessage());
                }
            }
            return $response;
        }
        return parent::getOrders();
    }

    /**
     * Get formatted price
     *
     * @param $amount
     * @param $currency
     * @param $storeId
     * @param $orderType
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getFormattedPrice($amount, $currency = null, $storeId = null, $orderType = null)
    {
        return $this->orderHelper->getPriceWithCurrency($this->priceCurrency, $amount, $currency, $storeId, $orderType);
    }

    /**
     * Get store currency code
     *
     * If store currency code is not passed then get store currency code from LSR
     *
     * @param $storeCurrencyCode
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCurrencyCode($storeCurrencyCode)
    {
        return ($storeCurrencyCode) ? $storeCurrencyCode : $this->lsr->getStoreCurrencyCode();
    }

    /**
     * Get the formatted date
     *
     * @param $date
     * @return string
     */
    public function getFormattedDate($date)
    {
        return $this->orderHelper->getFormattedDate($date);
    }

    /**
     * Get the time stamp
     *
     * @param $date
     * @return string
     */
    public function getFormattedDateToTimeStamp($date)
    {
        return $this->orderHelper->getDateTimeObject()->timestamp($date);
    }

    /**
     * Get order view url
     *
     * @param object $order
     * @param null $magOrder
     * @return string
     * @throws NoSuchEntityException
     */
    public function getViewUrl($order, $magOrder = null)
    {
        /*
        * Adding condition to only process if LSR is enabled.
        */
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getCustomerIntegrationOnFrontend()
        )) {
            if (!empty($magOrder) && !empty($order['Store Currency Code'])) {
                if ($order['Store Currency Code'] != $magOrder->getOrderCurrencyCode()) {
                    //$order->setCustomerOrderNo(null);
                    $order['Customer Order No'] = null;
                }
            }

            return $this->getUrl(
                'customer/order/view',
                [
                    'order_id' => $order['IdType'] == 'Order' && $order['Customer Document ID'] ?
                        $order['Customer Document ID'] : $order['Document ID'],
                    'type'     => $order['IdType'] == 'Order' && $order['Document ID'] ?
                        DocumentIdType::ORDER : $order['IdType']
                ]
            );
        }
        return parent::getViewUrl($order);
    }

    /**
     * Formulating reordering url
     *
     * @param object $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        return $this->getUrl('sales/order/reorder', ['order_id' => $order->getId()]);
    }

    /**
     * Formulating order canceling url
     *
     * @param OrderInterface $magentoOrder
     * @param $centralOrder
     * @return string
     */
    public function getCancelUrl(OrderInterface $magentoOrder, $centralOrder)
    {
        return $magentoOrder && $centralOrder ? $this->getUrl(
            'customer/order/cancel',
            [
                'magento_order_id' => $magentoOrder->getId(),
                'central_order_id' => $centralOrder->getId(),
                'id_type'          => $centralOrder->getIdType()
            ]
        ) : '';
    }

    /**
     * Check if order cancellation on frontend is enabled or not
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function orderCancellationOnFrontendIsEnabled()
    {
        return $this->lsr->orderCancellationOnFrontendIsEnabled();
    }

    /**
     * Get respective magento order given Central sales entry Object
     *
     * @param $order
     * @return array|OrderInterface
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

    /**
     * Register magento order in registry as current_mag_order
     *
     * @param $value
     * @return void
     */
    public function registerValueInRegistry($value)
    {
        $this->orderHelper->registerGivenValueInRegistry('current_mag_order', $value);
    }
}
