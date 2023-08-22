<?php

namespace Ls\Customer\Block\Order;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Api\SortOrderBuilder;

/**
 * Block being used for recent orders grid
 */
class Recent extends Template
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
     * @var SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var SortOrderBuilder
     */
    public $sortOrderBuilder;

    /**
     * Recent constructor.
     * @param Context $context
     * @param OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param CustomerSession $customerSession
     * @param LSR $LSR
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderHelper $orderHelper,
        PriceCurrencyInterface $priceCurrency,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        CustomerSession $customerSession,
        LSR $LSR,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderHelper           = $orderHelper;
        $this->priceCurrency         = $priceCurrency;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder      = $sortOrderBuilder;
        $this->customerSession       = $customerSession;
        $this->lsr                   = $LSR;
    }

    /**
     * Get recent order history
     *
     * @return array|bool|ArrayOfSalesEntry|null
     * @throws NoSuchEntityException
     */
    public function getOrderHistory()
    {
        $customerId = $this->customerSession->getCustomerId();
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $response = [];
            $orders   = $this->orderHelper->getCurrentCustomerOrderHistory(LSR::MAX_RECENT_ORDER);
            if ($orders) {
                try {
                    $response = $orders;
                } catch (Exception $e) {
                    $this->_logger->error($e->getMessage());
                }
            }
            return $response;
        }

        $sortOrder = $this->sortOrderBuilder->setField('created_at')->setDirection('DESC')->create();
        return $this->orderHelper->getOrders(
            $this->lsr->getCurrentStoreId(),
            LSR::MAX_RECENT_ORDER,
            false,
            $customerId,
            $sortOrder
        );
    }

    /**
     * Get formatted price
     *
     * @param $amount
     * @param $currency
     * @param $storeId
     * @param $orderType
     * @return mixed
     */
    public function getFormattedPrice($amount, $currency = null, $storeId = null, $orderType = null)
    {
        return $this->orderHelper->getPriceWithCurrency($this->priceCurrency, $amount, $currency, $storeId, $orderType);
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
     * @param $order
     * @param null $magOrder
     * @return string
     */
    public function getViewUrl($order, $magOrder = null)
    {
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

            if (!empty($magOrder) && !empty($order->getStoreCurrency())) {
                if ($order->getStoreCurrency() != $magOrder->getOrderCurrencyCode()) {
                    $order->setCustomerOrderNo(null);
                }
            }

            return $this->getUrl(
                'customer/order/view',
                [
                    'order_id' => $order->getIdType() == 'Order' && $order->getCustomerOrderNo() ?
                        $order->getCustomerOrderNo() : $order->getId(),
                    'type'     => $order->getIdType() == 'Order' && $order->getCustomerOrderNo() ?
                        DocumentIdType::ORDER : $order->getIdType()
                ]
            );
        }

        return $this->getUrl('sales/order/view', ['order_id' => $order->getId()]);
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
     * @param SalesEntry $centralOrder
     * @return string
     */
    public function getCancelUrl(OrderInterface $magentoOrder, SalesEntry $centralOrder)
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
     * @param $salesEntry
     * @return array|OrderInterface
     */
    public function getOrderByDocumentId($salesEntry)
    {
        return $this->orderHelper->getOrderByDocumentId($salesEntry);
    }

    /**
     * @return string
     */
    public function getOmniVersion()
    {
        return $this->lsr->getOmniVersion();
    }
}
