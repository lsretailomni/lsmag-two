<?php

namespace Ls\Customer\Block\Order;

use DateTime;
use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;

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
     * @var Proxy
     */
    public $customerSession;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * Recent constructor.
     * @param Context $context
     * @param OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Proxy $customerSession
     * @param LSR $LSR
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderHelper $orderHelper,
        PriceCurrencyInterface $priceCurrency,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Proxy $customerSession,
        LSR $LSR,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderHelper           = $orderHelper;
        $this->priceCurrency         = $priceCurrency;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession       = $customerSession;
        $this->lsr                   = $LSR;
    }

    /**
     * @return array|SalesEntry[]|null
     */
    public function getOrderHistory()
    {
        $response = [];
        $orders   = $this->orderHelper->getCurrentCustomerOrderHistory();
        if ($orders) {
            try {
                $response = $orders->getSalesEntry();
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }
        return $response;
    }

    /**
     * Function getFormatedPrice
     *
     * @param $amount
     *
     * @return string
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
     * @param $order
     * @param null $magOrder
     * @return string
     */
    public function getViewUrl($order, $magOrder = null)
    {
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

    /**
     * @param $order
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
