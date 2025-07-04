<?php

namespace Ls\Customer\Block\Order;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Totals class to return total lines
 */
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
     * Get formatted price
     *
     * @param $amount
     * @param $currency
     * @param $storeId
     * @return float
     * @throws NoSuchEntityException
     */
    public function getFormattedPrice($amount, $currency = null, $storeId = null)
    {
        return $this->orderHelper->getPriceWithCurrency($this->priceCurrency, $amount, $currency, $storeId);
    }

    /**
     * Get Total Tax
     *
     * @return mixed
     */
    public function getTotalTax()
    {
        $grandTotal     = $this->getGrandTotal();
        $lineItemObj    = ($this->getItems()) ? $this->getItems() : $this->getOrder()->getData();
        $totalNetAmount = $this->orderHelper->getFilterValues($lineItemObj, "Net Amount", "LSCMemberSalesBuffer");

        return ($grandTotal - $totalNetAmount);
    }

    /**
     * To fetch TotalNetAmount value from SalesEntryGetResult or SalesEntryGetReturnSalesResul
     *
     * @return float
     * @throws NoSuchEntityException
     */
    public function getTotalNetAmount()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder()->getData();
        $shipmentFee = $this->getShipmentChargeLineFee();
        return (float)$this->orderHelper->getParameterValues($lineItemObj, "TotalNetAmount") - (float)$shipmentFee
            + (float)$this->orderHelper->getParameterValues($lineItemObj, "TotalDiscount");
    }

    /**
     * To fetch TotalAmount value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     *
     * @return mixed
     */
    public function getGrandTotal()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder()->getData();
        return $this->orderHelper->getFilterValues($lineItemObj, "Gross Amount", "LSCMemberSalesBuffer");
    }

    /**
     * Get total amount
     *
     * @return float
     */
    public function getTotalAmount()
    {
        return $this->getGrandTotal() - $this->giftCardAmount - $this->loyaltyPointAmount;
    }

    /**
     * To fetch TotalDiscount value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     *
     * @return null
     */
    public function getTotalDiscount()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $this->orderHelper->getFilterValues($lineItemObj, "Discount Amount", "LSCMemberSalesBuffer");
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
        if (!is_array($orderLines)) {
            $orderLines = [$orderLines];
        }
        foreach ($orderLines as $line) {
            if ($line->getNumber() ==
                $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID, $this->lsr->getCurrentStoreId())) {
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
        if ($paymentLines) {
            if(!is_array($paymentLines)) {
                $paymentLines = [$paymentLines];
            }
            foreach ($paymentLines as $line) {
                if ($line->getEntryType() == 1) {
                    $tenderTypeId = $line->getNumber();
                    if (array_key_exists($tenderTypeId, $tenderTypeMapping)) {
                        $method    = $tenderTypeMapping[$tenderTypeId];
                        $methods[] = __($method);

                        $giftCardTenderId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
                        if ($giftCardTenderId == $tenderTypeId) {
                            $this->giftCardAmount = $line->getAmount();
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
        return ($this->getItems()) ? $this->getItems() : $this->getOrder(true)->getLscMemberSalesDocLine();
    }


    /**
     * Get Ordre payments
     *
     * @return mixed
     */
    public function getOrderPayments()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $lineItemObj["LSCMemberSalesDocLine"] ?? null;
    }

    /**
     * Convert loyalty points to amount
     *
     * @param $loyaltyPoints
     * @return float|int
     * @throws NoSuchEntityException|GuzzleException
     */
    public function convertLoyaltyPointsToAmount($loyaltyPoints)
    {
        $points = number_format((float)$loyaltyPoints, 2, '.', '');
        return $points * $this->loyaltyHelper->getPointRate();
    }
}
