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

    /**
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\SalesRule\Model\Validator $validator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SalesRule\Model\Validator $validator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        BasketHelper $basketHelper,
        LoyaltyHelper $loyaltyHelper
    ) {
        $this->setCode('discount');
        $this->eventManager = $eventManager;
        $this->calculator = $validator;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->basketHelper =  $basketHelper;
        $this->loyaltyHelper = $loyaltyHelper;
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
    ) {

        $basketData = $this->basketHelper->getBasketSessionValue();
        if (isset($basketData)) {
            $pointDiscount = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }

            $discountAmount = -$basketData->getTotalDiscount() - $pointDiscount;
            $total->addTotalAmount('discount', $discountAmount);
        }
        return $this;
    }

    /**
     * Add discount total information to address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        $amount=0;
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (isset($basketData)) {
            $pointDiscount = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }
            $amount = -$basketData->getTotalDiscount() - $pointDiscount;
        }

        if ($amount != 0) {
            $result = [
                'code' => $this->getCode(),
                'title' => __('Discount'),
                'value' => $amount
            ];
            $total->addTotalAmount('discount', $amount);
        }
        return $result;
    }
}