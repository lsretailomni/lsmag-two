<?php
namespace Ls\Customer\Block\Order;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use \Ls\Core\Model\LSR;

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
     * Totals constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
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
     * @return mixed
     */
    public function getGrandTotal()
    {
        $total = $this->getOrder()->getTotalAmount();
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
        $shipmentFee = $this->getShipmentChargeLineFee();
        $grandTotal = $this->getGrandTotal();
        $discount = $this->getTotalDiscount();
        $fee =  (float)$grandTotal + $discount - (float)$shipmentFee;
        return $fee;
    }
}