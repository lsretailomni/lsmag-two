<?php

namespace Ls\Omni\Model\Tax\Sales\Total\Quote;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;

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
     * Collect address discount amount
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        $discountAmount = $this->getTotalDiscount($quote);
        if ($discountAmount < 0) {
            $proActiveDiscount = $this->getProactiveDiscount($quote);
            $total->addTotalAmount('discount', $discountAmount);
            $total->addTotalAmount('subtotal', $proActiveDiscount);
            $this->checkoutSession->setProActiveCheck(0);
        } else {
            $total->addTotalAmount('discount', $discountAmount);
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

            $proActiveDiscount = $this->getProactiveDiscount($quote);
            $total->addTotalAmount('discount', $amount);
            $total->addTotalAmount('subtotal', $proActiveDiscount);
            $this->checkoutSession->setProActiveCheck(0);
        } else {
            $total->addTotalAmount('discount', $amount);
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
    public function getProactiveDiscount($quote)
    {
        $proActiveDiscount = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getProduct()->getFinalPrice() < $item->getProduct()->getPrice()) {
                $proActiveDiscount += (
                        $item->getProduct()->getPrice() - $item->getProduct()->getFinalPrice()
                    ) * $item->getQty();
            }
        }
        if ($proActiveDiscount > 0) {
            $this->checkoutSession->setProActiveDiscount($proActiveDiscount);
        }
        return $proActiveDiscount;
    }

    /**
     * @param $quote
     * @return float|int
     */
    public function getTotalDiscount($quote)
    {
        $amount = 0;
        $this->checkoutSession->setProActiveDiscount(0);
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (isset($basketData)) {
            $pointDiscount = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            $giftCardAmount = $quote->getLsGiftCardAmountUsed();
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }
            $amount = -$basketData->getTotalDiscount() - $pointDiscount - $giftCardAmount;
        }
        return $amount;
    }
}