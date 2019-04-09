<?php

namespace Ls\Omni\Plugin\Checkout\CustomerData;

use Magento\Checkout\Model\Session\Proxy as CheckoutSession;

/**
 * Class Cart
 * @package Ls\Omni\Plugin\Checkout\CustomerData
 */
class Cart
{

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var $quoteRepository
     */
    public $quoteRepository;

    /**
     * @var $checkoutHelper
     */
    public $checkoutHelper;

    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Helper\Data $checkoutHelper
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, array $result)
    {

        $totals = $this->checkoutSession->getQuote()->getTotals();
        $grandTotalAmount = $totals['grand_total']->getValue();
        if ($grandTotalAmount != null && $grandTotalAmount > 0) {
            if (isset($totals['discount'])) {
                $discount = abs($totals['discount']->getValue());
                $totalAmount = $grandTotalAmount + $discount;
                $result['subtotalAmount'] = $totalAmount;
                $result['subtotal'] = isset($totalAmount)
                    ? $this->checkoutHelper->formatPrice($totalAmount)
                    : 0;
            }
        }
        return $result;
    }
}