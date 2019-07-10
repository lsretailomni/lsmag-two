<?php
namespace Ls\Customer\Block\Order;

use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Recent
 * @package Ls\Customer\Block\Order
 */
class Recent extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Ls\Omni\Helper\OrderHelper
     */
    public $orderHelper;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /**
     * Recent constructor.
     * @param Context $context
     * @param OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderHelper $orderHelper,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderHelper = $orderHelper;
        $this->priceCurrency = $priceCurrency;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession = $customerSession;
    }

    /**
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\Order[]|null
     */
    public function getOrderHistory()
    {
        $response = [];
        $orders = $this->orderHelper->getCurrentCustomerOrderHistory();
        if ($orders) {
            try {
                $response = $orders->getOrder();
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            if ($response && !is_array($response)) {
                $obj = $response;
                $response = [$obj];
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
     * @throws \Exception
     */
    public function getFormattedDate($date)
    {
        // @codingStandardsIgnoreStart
        $formattedDate = new \DateTime($date);
        // @codingStandardsIgnoreEnd
        $result = $formattedDate->format('d/m/y');
        return $result;
    }

    /**
     * @param object $order
     * @return string
     */
    public function getViewUrl($order)
    {
        return $this->getUrl('customer/order/view', ['order_id' => $order->getDocumentId()]);
    }

    /**
     * @param $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        try {
            if ($order->getDocumentId()!=null) {
                return $this->getUrl('sales/order/reorder', ['order_id' => $order->getEntityId()]);
            } else {
                return parent::getReorderUrl($order);
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $documentId
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getOrderByDocumentId($documentId)
    {
        return $this->orderHelper->getOrderByDocumentId($documentId);
    }
}
