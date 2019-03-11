<?php

namespace Ls\Omni\Block\Cart;

use \Ls\Omni\Helper\LoyaltyHelper;

/**
 * Class Loyaltypoints
 * @package Ls\Omni\Block\Cart
 */
class Loyaltypoints extends \Magento\Checkout\Block\Cart\AbstractCart
{

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->loyaltyHelper = $loyaltyHelper;
        $this->_isScopePrivate = true;
    }

    /**
     * @return int
     */
    public function getMemberPoints()
    {
        return $this->loyaltyHelper->getMemberPoints();
    }

    /**
     * @return float|\Ls\Omni\Client\Ecommerce\Entity\GetPointRateResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getPointsRate()
    {
        return $this->loyaltyHelper->getPointRate();
    }

    /**
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        return $this->_checkoutSession->getQuote()->getBaseCurrencyCode();
    }

    /**
     * @return mixed
     */
    public function getLsPointsSpent()
    {
        return $this->getQuote()->getLsPointsSpent();
    }

}
