<?php

namespace Ls\Omni\Block\Cart;

use \Ls\Omni\Client\Ecommerce\Entity\CardGetPointBalanceResponse;
use \Ls\Omni\Client\Ecommerce\Entity\GetPointRateResponse;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\Helper\Data;

class LoyaltyPoints extends AbstractCart
{
    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var Data
     */
    public $priceHelper;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @param LoyaltyHelper $loyaltyHelper
     * @param Data $priceHelper
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper,
        Data $priceHelper,
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->loyaltyHelper   = $loyaltyHelper;
        $this->priceHelper = $priceHelper;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Get Member points for current customer
     *
     * @return int|CardGetPointBalanceResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getMemberPoints()
    {
        return $this->loyaltyHelper->getLoyaltyPointsAvailableToCustomer();
    }

    /**
     * Get point rate
     *
     * @return float|GetPointRateResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getPointsRate()
    {
        return $this->loyaltyHelper->getPointRate();
    }

    /**
     * Get base currency code
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBaseCurrencyCode()
    {
        return $this->_checkoutSession->getQuote()->getBaseCurrencyCode();
    }

    /**
     * Get ls points spent
     *
     * @return mixed
     */
    public function getLsPointsSpent()
    {
        return $this->getQuote()->getLsPointsSpent();
    }

    /**
     * Get formatted price
     *
     * @param $price
     * @return string
     */
    public function getFormattedPrice($price)
    {
        return $this->priceCurrency->format($price, false);
    }

    /**
     * Format value to two decimal places
     *
     * @param float $value
     * @return string
     */
    public function formatValue($value)
    {
        return $this->loyaltyHelper->formatValue($value);
    }
}
