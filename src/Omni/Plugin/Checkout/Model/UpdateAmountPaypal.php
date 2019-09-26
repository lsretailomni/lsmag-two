<?php

namespace Ls\Omni\Plugin\Checkout\Model;

/**
 * Class UpdateAmountPaypal
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class UpdateAmountPaypal
{
    const SUBTOTAL = 'subtotal';

    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;

    /**
     * UpdateAmountPaypal constructor.
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session\Proxy $checkoutSession
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
        $quote = $this->checkoutSession->getQuote();
        $paymentMethod = $quote->getPayment()->getMethod();

        $paypalMehodList = [
            'payflowpro',
            'payflow_link',
            'payflow_advanced',
            'braintree_paypal',
            'paypal_express_bml',
            'payflow_express_bml',
            'payflow_express',
            'paypal_express'];

        if (in_array($paymentMethod, $paypalMehodList)) {
            $result[self::SUBTOTAL] = $quote->getGrandTotal() - $quote->getShippingAddress()->getShippingAmount();
        }

        return $result;
    }
}
