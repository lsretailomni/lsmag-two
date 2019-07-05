<?php

namespace Ls\Customer\Block\Order;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;

/**
 * Class Totals
 * @package Ls\Customer\Block\Order
 */
class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry|null
     */
    public $coreRegistry = null;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var priceHelper
     */
    public $loyaltyHelper;

    /**
     * @var GiftCardAmount
     */
    public $giftCardAmount = 0;

    /**
     * @var LoyaltyAmount
     */
    public $loyaltyPointAmount = 0;

    /**
     * Totals constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param PriceCurrencyInterface $priceCurrency
     * @param LoyaltyHelper $loyaltyHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        LoyaltyHelper $loyaltyHelper,
        array $data = []
    )
    {
        $this->priceCurrency = $priceCurrency;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Ls\Omni\Client\Ecommerce\Entity\Order
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
        $grandTotal = $this->getGrandTotal();
        $totalNetAmount = $this->getTotalNetAmount();
        $totalTax = $grandTotal - $totalNetAmount;
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
        $orderLines = $this->getOrder()->getOrderLines()->getOrderLine();
        $fee = 0;
        foreach ($orderLines as $key => $line) {
            if ($line->getItemId() == LSR::LSR_SHIPMENT_ITEM_ID) {
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
        $grandTotal = $this->getGrandTotal();
        $discount = $this->getTotalDiscount();
        $fee = (float)$grandTotal + $discount - (float)$shipmentFee;
        return $fee;
    }

    /**
     * @return array
     */
    public function getLoyaltyGiftCardInfo()
    {
        // @codingStandardsIgnoreStart
        $paymentLines = $this->getOrder()->getOrderPayments()->getOrderPayment();
        if (!is_array($paymentLines)) {
            $singleLine = $paymentLines;
            $paymentLines = array($singleLine);
        }
        $methods = array();
        $giftCardInfo = array();
        $loyaltyInfo = array();
        // @codingStandardsIgnoreEnd
        foreach ($paymentLines as $line) {
            if ($line->getTenderType() == '0') {
                $methods[] = __('Cash');
            } elseif ($line->getTenderType() == '1') {
                $methods[] = __('Card');
            } elseif ($line->getTenderType() == '2') {
                $methods[] = __('Coupon');
            } elseif ($line->getTenderType() == '3') {
                $methods[] = __('Loyalty Points');
                $this->loyaltyPointAmount = $this->convertLoyaltyPointsToAmount($line->getPreApprovedAmount());
            } elseif ($line->getTenderType() == '4') {
                $methods[] = __('Gift Card');
                $this->giftCardAmount = $line->getPreApprovedAmount();
            } else {
                $methods[] = __('Pay At Store');
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