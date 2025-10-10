<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Cart;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Client\Ecommerce\Entity\CardGetPointBalanceResponse;
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
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param LoyaltyHelper $loyaltyHelper
     * @param Data $priceHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        public LoyaltyHelper $loyaltyHelper,
        public Data $priceHelper,
        public PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
    }

    /**
     * Get Member points for current customer
     *
     * @return int|CardGetPointBalanceResponse|ResponseInterface|null
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getMemberPoints()
    {
        return $this->loyaltyHelper->getLoyaltyPointsAvailableToCustomer();
    }

    /**
     * Get point rate
     *
     * @return float|int|string|null
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getPointsRate()
    {
        return $this->loyaltyHelper->getPointRate(null, 'LOY');
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
     * @param float $price
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
