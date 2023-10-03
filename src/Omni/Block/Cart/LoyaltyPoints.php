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
use Magento\Framework\View\Element\Template\Context;

class LoyaltyPoints extends AbstractCart
{
    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @param LoyaltyHelper $loyaltyHelper
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper,
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->loyaltyHelper   = $loyaltyHelper;
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
}
