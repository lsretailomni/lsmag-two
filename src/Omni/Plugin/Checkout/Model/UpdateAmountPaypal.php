<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\Session\Proxy;

/**
 * Class UpdateAmountPaypal
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class UpdateAmountPaypal
{
    const SUBTOTAL = 'subtotal';

    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * UpdateAmountPaypal constructor.
     * @param Proxy $checkoutSession
     */
    public function __construct(
        Proxy $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param $cart
     * @param $result
     * @return mixed
     */
    public function afterGetAmounts($cart, $result)
    {
        $quote         = $this->checkoutSession->getQuote();
        $paymentMethod = $quote->getPayment()->getMethod();

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

        if (in_array($paymentMethod, $paypalMehodList)) {
            $result[self::SUBTOTAL] = $quote->getGrandTotal() - $quote->getShippingAddress()->getShippingAmount();
        }

        return $result;
    }
}
