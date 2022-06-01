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
use Magento\SalesRule\Api\Data\DiscountDataInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleDiscountInterfaceFactory;
use Magento\SalesRule\Model\Validator;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Discount to apply different type of discounts
 */
class Discount extends \Magento\SalesRule\Model\Quote\Discount
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
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param Validator $validator
     * @param PriceCurrencyInterface $priceCurrency
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param Proxy $checkoutSession
     * @param RuleDiscountInterfaceFactory|null $discountInterfaceFactory
     * @param DiscountDataInterfaceFactory|null $discountDataInterfaceFactory
     */
    public function __construct(
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Validator $validator,
        PriceCurrencyInterface $priceCurrency,
        BasketHelper $basketHelper,
        LoyaltyHelper $loyaltyHelper,
        Proxy $checkoutSession,
        RuleDiscountInterfaceFactory $discountInterfaceFactory = null,
        DiscountDataInterfaceFactory $discountDataInterfaceFactory = null
    ) {
        parent::__construct(
            $eventManager,
            $storeManager,
            $validator,
            $priceCurrency,
            $discountInterfaceFactory,
            $discountDataInterfaceFactory
        );
        $this->eventManager = $eventManager;
        $this->calculator = $validator;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->basketHelper = $basketHelper;
        $this->loyaltyHelper = $loyaltyHelper;
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
        if (!$this->basketHelper->lsr->isEnabled()) {
            return parent::collect($quote, $shippingAssignment, $total);
        }
        $total->setData('discount_description', ''); //For fixing explode issue on graph ql
        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }
        $discountAmount = $this->getTotalDiscount($quote);
        $paymentDiscount = $this->getGiftCardLoyaltyDiscount($quote);
        $total->addTotalAmount('discount', $discountAmount);
        $total->addTotalAmount('grand_total', $paymentDiscount);
        $total->addBaseTotalAmount('discount', $discountAmount);
        $total->addBaseTotalAmount('grand_total', $paymentDiscount);

        if ($quote->getCouponCode()) {
            $total->setCouponCode($quote->getCouponCode());
        }
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
        if (!$this->basketHelper->lsr->isEnabled()) {
            return parent::fetch($quote, $total);
        }

        $result = null;
        $amount = $this->getTotalDiscount($quote);
        $title = __('Discount');
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
        $pointDiscount = 0;
        if ($quote->getLsPointsSpent() > 0) {
            $pointDiscount = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }
        }
        $giftCardAmount = $quote->getLsGiftCardAmountUsed();
        $amount = -$pointDiscount - $giftCardAmount;
        return $amount;
    }
}
