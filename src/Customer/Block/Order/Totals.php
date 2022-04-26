<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\LoyaltyHelper;
use Ls\OmniGraphQl\Model\Resolver\Order;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use \Ls\Omni\Helper\OrderHelper;

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
     * @var OrderHelper
     */
    public $orderHelper;

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
     * @param OrderHelper $orderHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        LoyaltyHelper $loyaltyHelper,
        LSR $lsr,
        OrderHelper $orderHelper,
        array $data = []
    ) {
        $this->priceCurrency     = $priceCurrency;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->coreRegistry      = $registry;
        $this->lsr               = $lsr;
        $this->orderHelper       = $orderHelper;
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
        return $this->orderHelper->getParameterValues($this->getOrder(),"TotalNetAmount");
    }

    /**
     * @return mixed
     */
    public function getGrandTotal()
    {
        return $this->orderHelper->getParameterValues($this->getOrder(),"TotalAmount");
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
        return $this->orderHelper->getParameterValues($this->getOrder(),"TotalDiscount");
    }

    /**
     * Get Shipment charge line fee
     *
     * @return float|int|null
     * @throws NoSuchEntityException
     */
    public function getShipmentChargeLineFee()
    {
        $orderLines = $this->getLines();
        $fee        = 0;
        foreach ($orderLines as $key => $line) {
            if ($line->getItemId() == $this->lsr->getStoreConfig(
                    LSR::LSR_SHIPMENT_ITEM_ID,
                    $this->lsr->getCurrentStoreId()
                )) {
                $fee = $line->getAmount();
                break;
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
        $paymentLines = $this->getOrderPayments();
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
     * @return mixed
     */
    public function getLines()
    {
        return $this->orderHelper->getParameterValues($this->getOrder(),"Lines");
    }


    /**
     * @return mixed
     */
    public function getOrderPayments()
    {
        return $this->orderHelper->getParameterValues($this->getOrder(),"Payments");
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
