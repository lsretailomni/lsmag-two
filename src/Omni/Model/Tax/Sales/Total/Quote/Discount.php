<?php

namespace Ls\Omni\Model\Tax\Sales\Total\Quote;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;

/**
 * Class Discount
 * @package Ls\Omni\Model\Tax\Sales\Total\Quote
 */
class Discount extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * Discount calculation object
     *
     * @var \Magento\SalesRule\Model\Validator
     */
    public $calculator;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    public $eventManager = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /** @var \Magento\Checkout\Model\Session\Proxy $checkoutSession */
    public $checkoutSession;

    /**
     * Discount constructor.
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\SalesRule\Model\Validator $validator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SalesRule\Model\Validator $validator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        BasketHelper $basketHelper,
        LoyaltyHelper $loyaltyHelper,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession
    )
    {
        $this->setCode('discount');
        $this->eventManager = $eventManager;
        $this->calculator = $validator;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->basketHelper = $basketHelper;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this|\Magento\Quote\Model\Quote\Address\Total\AbstractTotal
     * @throws \Exception
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        $discountAmount = $this->getTotalDiscount($quote);
        $paymentDiscount = $this->getGiftCardLoyaltyDiscount($quote);
        if ($discountAmount < 0) {
            $total->addTotalAmount('discount', $discountAmount);
            $total->addTotalAmount('grand_total', $paymentDiscount);
        } else {
            $total->addTotalAmount('discount', $discountAmount);
            $total->addTotalAmount('grand_total', $paymentDiscount);
            $quote->getBillingAddress()->setDiscountAmount(0)->save();
        }
        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array|null
     * @throws \Exception
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        $amount = $this->getTotalDiscount($quote);
        $title = __('Discount');
        if ($amount < 0) {
            $result = [
                'code' => $this->getCode(),
                'title' => $title,
                'value' => $amount
            ];

            $paymentDiscount = $this->getGiftCardLoyaltyDiscount($quote);
            $total->addTotalAmount('discount', $amount);
            $total->addTotalAmount('grand_total', $paymentDiscount);
        } else {
            $total->addTotalAmount('discount', $amount);
            $total->addTotalAmount('grand_total', 0);
            $quote->getBillingAddress()->setDiscountAmount(0)->save();
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     */
    public function clearValues(\Magento\Quote\Model\Quote\Address\Total $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);
        $total->addTotalAmount('discount', 0);
    }

    /**
     * @param $quote
     * @return float|int
     */
    public function getTotalDiscount($quote)
    {
        $amount = 0;
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (isset($basketData)) {
            $amount = -$basketData->getTotalDiscount();
        }
        return $amount;
    }

    /**
     * @param $quote
     * @return float|int
     */
    public function getGiftCardLoyaltyDiscount($quote)
    {
        $amount = 0;
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (isset($basketData)) {
            $pointDiscount = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            $giftCardAmount = $quote->getLsGiftCardAmountUsed();
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }
            $amount = -$pointDiscount - $giftCardAmount;
        }
        return $amount;
    }
}