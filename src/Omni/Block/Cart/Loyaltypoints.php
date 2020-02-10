<?php

namespace Ls\Omni\Block\Cart;

use \Ls\Omni\Client\Ecommerce\Entity\GetPointRateResponse;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Loyaltypoints
 * @package Ls\Omni\Block\Cart
 */
class Loyaltypoints extends AbstractCart
{

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * Loyaltypoints constructor.
     * @param LoyaltyHelper $loyaltyHelper
     * @param Context $context
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param Proxy $checkoutSession
     * @param array $data
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper,
        Context $context,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        Proxy $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->loyaltyHelper   = $loyaltyHelper;
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
     * @return float|GetPointRateResponse|ResponseInterface|null
     */
    public function getPointsRate()
    {
        return $this->loyaltyHelper->getPointRate();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
