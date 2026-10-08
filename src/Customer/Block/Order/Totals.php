<?php
declare(strict_types=1);

namespace Ls\Customer\Block\Order;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\LSCMemberSalesBuffer;
use \Ls\Omni\Helper\Data as DataHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderRepository;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Totals class to return total lines
 */
class Totals extends AbstractOrderBlock
{
    /**
     * @var TaxConfig
     */
    public $taxConfig;

    /**
     * @var int
     */
    public $giftCardAmount = 0;

    /**
     * @var int
     */
    public $loyaltyPointAmount = 0;

    /**
     * @var array
     */
    public $voucherEntries = [];

    /**
     * @var array
     */
    public $giftCardEntries = [];

    /**
     * @param Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     * @param DataHelper $dataHelper
     * @param PriceHelper $priceHelper
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerSession $customerSession
     * @param CountryFactory $countryFactory
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Http $request
     * @param TaxConfig $taxConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        LoyaltyHelper $loyaltyHelper,
        LSR $lsr,
        OrderHelper $orderHelper,
        DataHelper $dataHelper,
        PriceHelper $priceHelper,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerSession $customerSession,
        CountryFactory $countryFactory,
        \Magento\Framework\App\Http\Context $httpContext,
        Http $request,
        TaxConfig $taxConfig,
        array $data = []
    ) {
        $this->taxConfig = $taxConfig;
        parent::__construct(
            $context,
            $priceCurrency,
            $loyaltyHelper,
            $lsr,
            $orderHelper,
            $dataHelper,
            $priceHelper,
            $orderRepository,
            $searchCriteriaBuilder,
            $customerSession,
            $countryFactory,
            $httpContext,
            $request,
            $data
        );
    }

    /**
     * Whether Sales Totals Subtotal should display excl-tax value only
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalExclTax()
    {
        return $this->taxConfig->displaySalesSubtotalExclTax($this->lsr->getCurrentStoreId());
    }

    /**
     * Whether Sales Totals Subtotal should display both excl-tax and incl-tax values
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalBoth()
    {
        return $this->taxConfig->displaySalesSubtotalBoth($this->lsr->getCurrentStoreId());
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
     * When LS Central has already folded tax into Gross Amount, Gross - Net is arithmetically
     * guaranteed to equal the tax (that's what "folded in" means) - so that's used directly
     * rather than trusting every line's VatAmount to be populated for this tax model. Only when
     * Gross Amount equals Net Amount (Gross - Net would be 0, e.g. US sales-tax stores) do we
     * fall back to summing each line's VatAmount instead.
     *
     * @return float
     */
    public function getTotalTax()
    {
        if ($this->isTaxFoldedIntoAmount()) {
            return $this->getGrandTotal() - $this->getNetAmount();
        }

        $totalTax   = 0.0;
        $orderLines = $this->getItems();
        if (!$orderLines) {
            return $totalTax;
        }

        if (!is_array($orderLines)) {
            $orderLines = [$orderLines];
        }

        foreach ($orderLines as $line) {
            $totalTax += (float)$line->getVatAmount();
        }

        return $totalTax;
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
     * Whether LS Central has already folded tax into Gross Amount for this order.
     *
     * VAT-style stores compute Gross Amount as Net Amount + tax, so the two differ. US
     * sales-tax stores track tax only per-line (VatAmount) and leave Gross Amount equal to Net
     * Amount. This flags which case applies so getGrandTotal()/getSubtotal() only add the
     * computed tax (getTotalTax()) when Gross Amount hasn't already included it - avoiding
     * double-counting for stores where it has.
     *
     * @return bool
     */
    public function isTaxFoldedIntoAmount()
    {
        if (!empty($lscMemberSalesBuffer = current($this->getCurrentTransaction()))) {
            return abs(
                (float)$lscMemberSalesBuffer->getGrossAmount() - (float)$lscMemberSalesBuffer->getNetAmount()
            ) > 0.0001;
        }

        return false;
    }

    /**
     * To fetch TotalAmount value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     *
     * @return float
     */
    public function getGrandTotal()
    {
        if (!empty($lscMemberSalesBuffer = current($this->getCurrentTransaction()))) {
            $gross = (float)$lscMemberSalesBuffer->getGrossAmount();

            return $this->isTaxFoldedIntoAmount() ? $gross : $gross + $this->getTotalTax();
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
        $voucherTotal = array_sum(array_column($this->voucherEntries, 'amount'));
        return $this->getGrandTotal() - $this->giftCardAmount - $this->loyaltyPointAmount - $voucherTotal;
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
     * Get the Shipment & Handling line's own VAT amount
     *
     * @return float
     */
    public function getShipmentTax()
    {
        $orderLines = $this->getItems();
        $tax        = 0.0;
        if (!$orderLines) {
            return $tax;
        }

        if (!is_array($orderLines)) {
            $orderLines = [$orderLines];
        }
        foreach ($orderLines as $line) {
            if ($line->getNumber() ==
                $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID, $this->lsr->getCurrentStoreId())) {
                $tax = (float)$line->getVatAmount();
                break;
            }
        }
        return $tax;
    }

    /**
     * Get Subtotal (tax-inclusive, pre-discount, merchandise-only)
     *
     * getGrandTotal() is already tax-inclusive (natively, or via its own fold-check), so the
     * merchandise-only subtotal is obtained by removing the Shipment & Handling line - adding
     * its own tax only when getGrandTotal() needed to add tax itself, since in that case the
     * Shipment line's Amount (like all line amounts for this tax model) is still tax-exclusive.
     *
     * @return float
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getSubtotal()
    {
        $this->getLoyaltyGiftCardInfo();
        $shipmentFee     = (float)$this->getShipmentChargeLineFee();
        $shippingInclTax = $this->isTaxFoldedIntoAmount() ? $shipmentFee : $shipmentFee + $this->getShipmentTax();
        $discount        = $this->getTotalDiscount();

        return $this->getGrandTotal() + $discount - $shippingInclTax;
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

                        $loyaltyTenderId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_LOYALTYPOINTS_TENDER_TYPE);
                        if ($loyaltyTenderId == $tenderTypeId) {
                            $this->loyaltyPointAmount = $this->formatLoyaltyPoints($line->getAmountInCurrency());
                        }
                    } else {
                        $methods[] = __('Unknown');
                    }
                }
            }
        }

        // Build gift card amount and voucher entries from the Magento order's stored POS data entries.
        // Reading from ls_pos_data_entries avoids treating regular card payment lines as vouchers.
        $magOrder   = $this->getMagOrder();
        $allEntries = json_decode((string)($magOrder ? $magOrder->getLsPosDataEntries() : null), true) ?? [];
        foreach ($allEntries as $entry) {
            $entryType = $entry['entry_type'] ?? '';
            $entryNo   = $entry['entry_no'] ?? '';
            $amount    = (float)($entry['amount'] ?? 0);
            if (strtoupper($entryType) === 'GIFTCARDNO') {
                $this->giftCardAmount += $amount;
                $this->giftCardEntries[] = [
                    'entry_type' => 'Gift Card',
                    'entry_no'   => $entryNo,
                    'amount'     => $amount,
                ];
            } else {
                $this->voucherEntries[] = [
                    'entry_type' => $entryType ?: 'Voucher',
                    'entry_no'   => $entryNo,
                    'amount'     => $amount,
                ];
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
        $order                = $this->getOrder();
        $requiredTransaction  = [];
        if ($order) {
            if (is_array($order->getLscMemberSalesBuffer()) && !empty($order->getLscMemberSalesBuffer())) {
                $documentId = $this->_request->getParam('order_id');
            } elseif ($order instanceof LSCMemberSalesBuffer) {
                $documentId = $order->getDocumentId();
            } else {
                $documentId = $order->getLscMemberSalesBuffer()->getDocumentId() ?? $this->_request->getParam('order_id');
            }
            $newDocumentId        = $this->_request->getParam('new_order_id');
            $newDocumentId        = ($newDocumentId) ? [$newDocumentId] : [];

            $lscMemberSalesBuffer = $order instanceof LSCMemberSalesBuffer ? [$order] : (is_array($order->getLscMemberSalesBuffer()) ?
                $order->getLscMemberSalesBuffer() :
                [$order->getLscMemberSalesBuffer()]);

            foreach ($lscMemberSalesBuffer as $transaction) {
                if ($transaction->getDocumentId() == $documentId || in_array($transaction->getDocumentId(),
                        $newDocumentId)) {
                    $requiredTransaction[] = $transaction;
                    break;
                }
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
        $order      = $this->getOrder(true);
        $orderLines = [];
        if ($order) {
            $orderLines = $order->getLscMemberSalesDocLine();
            $orderLines = $orderLines && is_array($orderLines) ?
                $orderLines : (($orderLines && !is_array($orderLines)) ? [$orderLines] : []);
            $documentId = $this->_request->getParam('order_id');
            $newDocumentId = $this->_request->getParam('new_order_id');
            $newDocumentId = ($newDocumentId) ? [$newDocumentId] : [];
            $isCreditMemo = $this->orderHelper->getGivenValueFromRegistry('current_detail') == 'creditmemo';

            foreach ($orderLines as $key => $line) {
                if ((!$isCreditMemo && $line->getDocumentId() !== $documentId) ||
                    ($isCreditMemo && $newDocumentId && !in_array($line->getDocumentId(), $newDocumentId)) ||
                    $line->getEntryType() == 1
                ) {
                    unset($orderLines[$key]);
                }
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
        if ($this->getOrder(true) && !empty($this->getOrder(true)->getLscMemberSalesDocLine())) {
            return is_array($this->getOrder(true)->getLscMemberSalesDocLine()) ?
                $this->getOrder(true)->getLscMemberSalesDocLine() :
                [$this->getOrder(true)->getLscMemberSalesDocLine()];
        }

        return null;
    }

    /**
     * Convert loyalty points to amount
     *
     * @param $loyaltyPoints
     * @return float|int
     */
    public function formatLoyaltyPoints($loyaltyPoints)
    {
        return number_format((float)$loyaltyPoints, 2, '.', '');
    }
}
