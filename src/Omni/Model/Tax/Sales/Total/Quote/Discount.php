<?php

namespace Ls\Omni\Model\Tax\Sales\Total\Quote;

use Exception;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\SalesRule\Model\Validator;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Discount
 * @package Ls\Omni\Model\Tax\Sales\Total\Quote
 */
class Discount extends AbstractTotal
{
    /**
     * Discount calculation object
     *
     * @var Validator
     */
    public $calculator;

    /**
     * Core event manager proxy
     *
     * @var ManagerInterface
     */
    public $eventManager = null;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var PriceCurrencyInterface
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

    /** @var Proxy $checkoutSession */
    public $checkoutSession;

    /**
     * Discount constructor.
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param Validator $validator
     * @param PriceCurrencyInterface $priceCurrency
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param Proxy $checkoutSession
     */
    public function __construct(
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Validator $validator,
        PriceCurrencyInterface $priceCurrency,
        BasketHelper $basketHelper,
        LoyaltyHelper $loyaltyHelper,
        Proxy $checkoutSession
    ) {
        $this->setCode('discount');
        $this->eventManager    = $eventManager;
        $this->calculator      = $validator;
        $this->storeManager    = $storeManager;
        $this->priceCurrency   = $priceCurrency;
        $this->basketHelper    = $basketHelper;
        $this->loyaltyHelper   = $loyaltyHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this|AbstractTotal
     * @throws Exception
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        if ($shippingAssignment->getShipping()->getAddress()->getAddressType() == 'billing') {
            return $this;
        }
        $discountAmount  = $this->getTotalDiscount($quote);
        $paymentDiscount = $this->getGiftCardLoyaltyDiscount($quote);
        $total->addTotalAmount('discount', $discountAmount);
        $total->addTotalAmount('grand_total', $paymentDiscount);
        $total->addBaseTotalAmount('discount', $discountAmount);
        $total->addBaseTotalAmount('grand_total', $paymentDiscount);
        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     * @throws Exception
     */
    public function fetch(Quote $quote, Total $total)
    {
        $result = null;
        $amount = $this->getTotalDiscount($quote);
        $title  = __('Discount');
        if ($amount < 0) {
            $result = [
                'code'  => $this->getCode(),
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
     * @param Total $total
     */
    public function clearValues(Total $total)
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
        $amount     = 0;
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
        $amount     = 0;
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (isset($basketData)) {
            $pointDiscount  = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            $giftCardAmount = $quote->getLsGiftCardAmountUsed();
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }
            $amount = -$pointDiscount - $giftCardAmount;
        }
        return $amount;
    }
}
