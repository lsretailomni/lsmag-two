<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;

/**
 * Class Totals
 *  Ls\Customer\Block\Order
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
     * Get items.
     *
     * @return array|null
     */
    public function getItems()
    {
        return $this->getData('items');
    }

    /**
     * @param $amount
     * @return float
     */
    public function getFormattedPrice($amount)
    {
        return $this->priceCurrency->format($amount, false, 2);
    }

    /**
     * @return mixed
     */
    public function getTotalTax()
    {
        $grandTotal     = $this->getGrandTotal();
        $totalNetAmount = $this->getTotalNetAmount();
        return ($grandTotal - $totalNetAmount);
    }

    /**
     * To fetch TotalNetAmount value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     * depending on the structure of SalesEntry node
     * @return mixed
     */
    public function getTotalNetAmount()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $this->orderHelper->getParameterValues($lineItemObj, "TotalNetAmount");
    }

    /**
     * To fetch TotalAmount value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     * depending on the structure of SalesEntry node
     * @return mixed
     */
    public function getGrandTotal()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $this->orderHelper->getParameterValues($lineItemObj, "TotalAmount");
    }

    /**
     * @return float|GiftCardAmount|LoyaltyAmount
     */
    public function getTotalAmount()
    {
        return $this->getGrandTotal() - $this->giftCardAmount - $this->loyaltyPointAmount;
    }

    /**
     * To fetch TotalDiscount value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     * depending on the structure of SalesEntry node
     * @return mixed
     */
    public function getTotalDiscount()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $this->orderHelper->getParameterValues($lineItemObj, "TotalDiscount");
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
     * @throws NoSuchEntityException
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
     * @throws NoSuchEntityException
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
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $this->orderHelper->getParameterValues($lineItemObj, "Lines");
    }


    /**
     * @return mixed
     */
    public function getOrderPayments()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $this->orderHelper->getParameterValues($lineItemObj, "Payments");
    }

    /**
     * @param $loyaltyPoints
     * @return float|int
     * @throws NoSuchEntityException
     */
    public function convertLoyaltyPointsToAmount($loyaltyPoints)
    {
        $points = number_format((float)$loyaltyPoints, 2, '.', '');
        return $points * $this->loyaltyHelper->getPointRate();
    }
}
