<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ls\Customer\Block\Order;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Sales order history block
 *
 * @api
 * @since 100.0.2
 */
class History extends \Magento\Sales\Block\Order\History
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
     * History constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Ls\Omni\Helper\OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Ls\Omni\Helper\OrderHelper $orderHelper,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->orderHelper = $orderHelper;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }

    /**
     * @return \Ls\Omni\Client\Ecommerce\Entity\Order[]
     */
    public function getOrderHistory()
    {
        $response = $this->orderHelper->getCurrentCustomerOrderHistory()->getOrder();
        return $response;
    }

    /**
     * Function getFormatedPrice
     *
     * @param float $price
     *
     * @return string
     */
    public function getFormatedPrice($amount)
    {
        $price = $this->priceCurrency->format($amount, false, 2);
        return $price;
    }

    /**
     * Function getFormatedLoyaltyPoints
     *
     * @param $points
     *
     * @return string
     */
    public function getFormatedLoyaltyPoints($points)
    {
        $points = number_format((float)$points, 2, '.', '');
        return $points;
    }
    /**
     * Function getFormatedDate
     *
     * @param $date
     *
     * @return string
     */
    public function getFormatedDate($date)
    {
        // @codingStandardsIgnoreStart
        $formatedDate = new \DateTime($date);
        // @codingStandardsIgnoreEnd
        $result = $formatedDate->format('d/m/y');
        return $result;
    }
}
