<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interceptor to intercept getAmounts method for fixing paypal error on checkout
 */
class UpdateAmountPaypal
{
    const SUBTOTAL = 'subtotal';

    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @param Proxy $checkoutSession
     * @param LoyaltyHelper $loyaltyHelper
     */
    public function __construct(
        Proxy $checkoutSession,
        LoyaltyHelper $loyaltyHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->loyaltyHelper   = $loyaltyHelper;
    }

    /**
     * After plugin to fix cart item and order amounts mismatch
     *
     * @param $cart
     * @param $result
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterGetAmounts($cart, $result)
    {
        $paypalMehodList = [
            'payflowpro',
            'payflow_link',
            'payflow_advanced',
            'braintree_paypal',
            'paypal_express_bml',
            'payflow_express_bml',
            'payflow_express',
            'paypal_express'
        ];

        $quote         = $this->checkoutSession->getQuote();
        $paymentMethod = $quote->getPayment()->getMethod();
        $pointRate     = $this->loyaltyHelper->getPointRate();
        $loyaltyPoints = $pointRate > 0 ? $pointRate * $quote->getLsPointsSpent() : 0;

        if (in_array($paymentMethod, $paypalMehodList)) {
            $result[self::SUBTOTAL] -= $loyaltyPoints + $quote->getLsGiftCardAmountUsed();
        }

        return $result;
    }
}
