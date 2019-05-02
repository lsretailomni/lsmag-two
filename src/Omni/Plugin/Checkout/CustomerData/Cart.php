<?php

namespace Ls\Omni\Plugin\Checkout\CustomerData;

use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use \Ls\Omni\Helper\Data;

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

    /**
     * @var Data
     */
    public $data;

    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        Data $data
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutHelper = $checkoutHelper;
        $this->data = $data;
    }

    /**
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, array $result)
    {
        $quote = $this->checkoutSession->getQuote();
        $grandTotalAmount = $this->data->getOrderBalance(
            $quote->getLsGiftCardAmountUsed(),
            $quote->getLsPointsSpent()
        );
        if ($grandTotalAmount > 0) {
            $result['subtotalAmount'] = $grandTotalAmount;
            $result['subtotal'] = isset($grandTotalAmount)
                ? $this->checkoutHelper->formatPrice($grandTotalAmount)
                : 0;
        }
        return $result;
    }
}