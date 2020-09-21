<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Totals
 * @package Ls\Customer\Block\Order
 */
class Totals extends Template
{
    /**
     * @var Registry|null
     */
    public $coreRegistry = null;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var int
     */
    public $giftCardAmount = 0;

    /**
     * @var int
     */
    public $loyaltyPointAmount = 0;

    /** @var  LSR $lsr */
    public $lsr;

    /**
     * Totals constructor.
     * @param Context $context
     * @param Registry $registry
     * @param PriceCurrencyInterface $priceCurrency
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        LoyaltyHelper $loyaltyHelper,
        LSR $lsr,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->coreRegistry  = $registry;
        $this->lsr           = $lsr;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @return SalesEntry
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @param $amount
     * @return float
     */
    public function getFormattedPrice($amount)
    {
        $price = $this->priceCurrency->format($amount, false, 2);
        return $price;
    }

    /**
     * @return mixed
     */
    public function getTotalTax()
    {
        $grandTotal     = $this->getGrandTotal();
        $totalNetAmount = $this->getTotalNetAmount();
        $totalTax       = $grandTotal - $totalNetAmount;
        return $totalTax;
    }

    /**
     * @return mixed
     */
    public function getTotalNetAmount()
    {
        $total = $this->getOrder()->getTotalNetAmount();
        return $total;
    }

    /**
     * @return float
     */
    public function getGrandTotal()
    {
        $total = $this->getOrder()->getTotalAmount();

        return $total;
    }

    /**
     * @return float|GiftCardAmount|LoyaltyAmount
     */
    public function getTotalAmount()
    {
        $total = $this->getGrandTotal() - $this->giftCardAmount - $this->loyaltyPointAmount;

        return $total;
    }

    /**
     * @return mixed
     */
    public function getTotalDiscount()
    {
        $total = $this->getOrder()->getTotalDiscount();
        return $total;
    }

    /**
     * @return float|int
     */
    public function getShipmentChargeLineFee()
    {
        $orderLines = $this->getOrder()->getLines();
        $fee        = 0;
        foreach ($orderLines as $key => $line) {
            if ($line->getItemId() == $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID)) {
                $fee = $line->getAmount();
            }
        }
        return $fee;
    }

    /**
     * @return mixed
     */
    public function getSubtotal()
    {
        $this->getLoyaltyGiftCardInfo();
        $shipmentFee = $this->getShipmentChargeLineFee();
        $grandTotal  = $this->getGrandTotal();
        $discount    = $this->getTotalDiscount();
        $fee         = (float)$grandTotal + $discount - (float)$shipmentFee;
        return $fee;
    }

    /**
     * @return array
     */
    public function getLoyaltyGiftCardInfo()
    {
        // @codingStandardsIgnoreStart
        $paymentLines = $this->getOrder()->getPayments();
        $methods      = [];
        $giftCardInfo = [];
        $loyaltyInfo  = [];
        // @codingStandardsIgnoreEnd
        foreach ($paymentLines as $line) {
            if ($line->getTenderType() == '0') {
                $methods[] = __('Cash');
            } elseif ($line->getTenderType() == '1') {
                $methods[] = __('Card');
            } elseif ($line->getTenderType() == '2') {
                $methods[] = __('Coupon');
            } elseif ($line->getTenderType() == '3') {
                $methods[]                = __('Loyalty Points');
                $this->loyaltyPointAmount = $this->convertLoyaltyPointsToAmount($line->getAmount());
            } elseif ($line->getTenderType() == '4') {
                $methods[]            = __('Gift Card');
                $this->giftCardAmount = $line->getAmount();
            } else {
                $methods[] = __('Unknown');
            }
        }
        return [implode(', ', $methods), $giftCardInfo, $loyaltyInfo];
    }

    /**
     * @param $loyaltyAmount
     * @return float|int
     */
    public function convertLoyaltyPointsToAmount($loyaltyPoints)
    {
        $points = number_format((float)$loyaltyPoints, 2, '.', '');
        return $points * $this->loyaltyHelper->getPointRate();
    }
}
