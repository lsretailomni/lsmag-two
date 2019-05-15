<?php
namespace Ls\Customer\Block\Order;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use \Ls\Omni\Helper\OrderHelper;

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
     * Recent constructor.
     * @param Context $context
     * @param OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderHelper $orderHelper,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderHelper = $orderHelper;
        $this->priceCurrency = $priceCurrency;
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
     * Function getFormatedDate
     *
     * @param $date
     *
     * @return string
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
}
