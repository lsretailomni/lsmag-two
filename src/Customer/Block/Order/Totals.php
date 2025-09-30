<?php
declare(strict_types=1);

namespace Ls\Customer\Block\Order;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\LSCMemberSalesBuffer;
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
     * @return float
     */
    public function getTotalTax()
    {
        $grandTotal     = $this->getGrandTotal();

        $totalNetAmount = $this->getNetAmount();

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
        $totalNetAmount = $this->getNetAmount();

        $totalDiscount = $this->getTotalDiscount();

        $shipmentFee = $this->getShipmentChargeLineFee();

        return $totalNetAmount - (float)$shipmentFee + $totalDiscount;
    }

    /**
     * Get net amount from central order
     *
     * @return float
     */
    public function getNetAmount()
    {
        if (!empty($lscMemberSalesBuffer = current($this->getCurrentTransaction()))) {
            return $lscMemberSalesBuffer->getNetAmount();
        }

        return 0.0;
    }

    /**
     * To fetch TotalAmount value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     *
     * @return float
     */
    public function getGrandTotal()
    {
        if (!empty($lscMemberSalesBuffer = current($this->getCurrentTransaction()))) {
            return $lscMemberSalesBuffer->getGrossAmount();
        }

        return 0.0;
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
     * @return float
     */
    public function getTotalDiscount()
    {
        if (!empty($lscMemberSalesBuffer = current($this->getCurrentTransaction()))) {
            return $lscMemberSalesBuffer->getDiscountAmount();
        }

        return 0.0;
    }

    /**
     * Get Shipment charge line fee
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getShipmentChargeLineFee()
    {
        $orderLines = $this->getItems();
        $fee        = 0;
        if (!$orderLines) {
            return $fee;
        }
        
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
     * @return float
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getSubtotal()
    {
        $this->getLoyaltyGiftCardInfo();
        $shipmentFee = $this->getShipmentChargeLineFee();
        $grandTotal  = $this->getGrandTotal();
        $discount    = $this->getTotalDiscount();

        return $grandTotal + $discount - (float)$shipmentFee;
    }

    /**
     * Get Loyalty gift card info
     *
     * @return array
     * @throws NoSuchEntityException|GuzzleException
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
            if (!is_array($paymentLines)) {
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
     * Fetch current transaction
     *
     * @return array
     */
    public function getCurrentTransaction()
    {
        $order = $this->getOrder();
        $documentId = $order->getDocumentId() ?? $this->_request->getParam('order_id');
        $newDocumentId = $this->_request->getParam('new_order_id');
        $requiredTransaction = [];
        $lscMemberSalesBuffer = $order instanceof LSCMemberSalesBuffer ? [$order] : (is_array($order->getLscMemberSalesBuffer()) ?
            $order->getLscMemberSalesBuffer() :
            [$order->getLscMemberSalesBuffer()]);

        foreach ($lscMemberSalesBuffer as $transaction) {
            if ($transaction->getDocumentId() == $documentId || in_array($transaction->getDocumentId(), $newDocumentId)) {
                $requiredTransaction[] = $transaction;
                break;
            }
        }

        return $requiredTransaction;
    }

    /**
     * Get orderLines either using magento order or central order object
     *
     * @return array
     */
    public function getItems()
    {
        $order = $this->getOrder(true);
        $orderLines = $order->getLscMemberSalesDocLine();
        $orderLines = $orderLines && is_array($orderLines) ?
            $orderLines : (($orderLines && !is_array($orderLines)) ? [$orderLines] : []);
        $documentId = $this->_request->getParam('order_id');
        $newDocumentId = $this->_request->getParam('new_order_id');
        $isCreditMemo = $this->orderHelper->getGivenValueFromRegistry('current_detail') == 'creditmemo';

        foreach ($orderLines as $key => $line) {
            if ((!$isCreditMemo && $line->getDocumentId() !== $documentId) ||
                ($isCreditMemo && $newDocumentId && !in_array($line->getDocumentId(), $newDocumentId)) ||
                $line->getEntryType() == 1
            ) {
                unset($orderLines[$key]);
            }
        }

        return $orderLines;
    }

    /**
     * Get order payments
     *
     * @return array|null
     */
    public function getOrderPayments()
    {
        if ($this->getOrder() && !empty($this->getOrder()->getLscMemberSalesDocLine())) {
            return is_array($this->getOrder()->getLscMemberSalesDocLine()) ?
                $this->getOrder()->getLscMemberSalesDocLine() :
                [$this->getOrder()->getLscMemberSalesDocLine()];
        }

        return null;
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
