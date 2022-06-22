<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\PaymentType;
use Magento\Framework\Exception\NoSuchEntityException;

class Totals extends AbstractOrderBlock
{
    /**
     * @var int
     */
    public $giftCardAmount = 0;

    /**
     * @var int
     */
    public $loyaltyPointAmount = 0;

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
     * @return float
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
     * Get Subtotal
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getSubtotal()
    {
        $this->getLoyaltyGiftCardInfo();
        $shipmentFee = $this->getShipmentChargeLineFee();
        $grandTotal  = $this->getGrandTotal();
        $discount    = $this->getTotalDiscount();
        return (float)$grandTotal + $discount - (float)$shipmentFee;
    }

    /**
     * Get Loyalty gift card info
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getLoyaltyGiftCardInfo()
    {
        // @codingStandardsIgnoreStart
        $paymentLines      = $this->getOrderPayments();
        $methods           = [];
        $giftCardInfo      = [];
        $loyaltyInfo       = [];
        $tenderTypeMapping = $this->dataHelper->getTenderTypesPaymentMapping();
        foreach ($paymentLines as $line) {
            if ($line->getType() === PaymentType::PAYMENT || $line->getType() === PaymentType::PRE_AUTHORIZATION
                || $line->getType() === PaymentType::NONE) {
                $tenderTypeId = $line->getTenderType();
                if (array_key_exists($tenderTypeId, $tenderTypeMapping)) {
                    $method    = $tenderTypeMapping[$tenderTypeId];
                    $methods[] = __($method);
                    if (!empty($line->getCardNo())) {
                        $giftCardTenderId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
                        if ($giftCardTenderId == $tenderTypeId) {
                            $this->giftCardAmount = $line->getAmount();
                        }
                    }
                    $loyaltyTenderId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_LOYALTYPOINTS_TENDER_TYPE);
                    if ($loyaltyTenderId == $tenderTypeId) {
                        $this->loyaltyPointAmount = $this->convertLoyaltyPointsToAmount($line->getAmount());
                    }
                } else {
                    $methods[] = __('Unknown');
                }
            }
        }
        return [implode(', ', $methods), $giftCardInfo, $loyaltyInfo];
    }

    /**
     * Get lines
     *
     * @return mixed
     */
    public function getLines()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $this->orderHelper->getParameterValues($lineItemObj, "Lines");
    }


    /**
     * Get Ordre payments
     *
     * @return mixed
     */
    public function getOrderPayments()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $this->orderHelper->getParameterValues($lineItemObj, "Payments");
    }

    /**
     * Convert loyalty points to amount
     *
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
