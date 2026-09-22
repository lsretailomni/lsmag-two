<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\PaymentType;
use \Ls\Omni\Helper\Data as DataHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
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
     * @var int
     */
    public $giftCardAmount = 0;

    /**
     * @var int
     */
    public $loyaltyPointAmount = 0;

    /**
     * @var TaxConfig
     */
    public $taxConfig;

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
            $data
        );
    }

    /**
     * Whether the "Display Sales Totals" config is set to show subtotal excluding tax only
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalExclTax()
    {
        return $this->taxConfig->displaySalesSubtotalExclTax($this->lsr->getCurrentStoreId());
    }

    /**
     * Whether the "Display Sales Totals" config is set to show subtotal including and excluding tax
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalBoth()
    {
        return $this->taxConfig->displaySalesSubtotalBoth($this->lsr->getCurrentStoreId());
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
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        $totalNetAmount = $this->orderHelper->getParameterValues($lineItemObj, "TotalNetAmount");
        return ($grandTotal - $totalNetAmount);
    }

    /**
     * To fetch TotalNetAmount value from SalesEntryGetResult or SalesEntryGetReturnSalesResul
     *
     * @return mixed
     */
    public function getTotalNetAmount()
    {
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
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
        $lineItemObj = ($this->getItems()) ? $this->getItems() : $this->getOrder();
        return $this->orderHelper->getParameterValues($lineItemObj, "TotalAmount");
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
        if ($paymentLines) {
            foreach ($paymentLines as $line) {
                if ($line->getType() === PaymentType::PAYMENT || $line->getType() === PaymentType::PRE_AUTHORIZATION
                    || $line->getType() === PaymentType::NONE) {
                    $tenderTypeId = $line->getTenderType();
                    if (array_key_exists($tenderTypeId, $tenderTypeMapping)) {
                        $method    = $tenderTypeMapping[$tenderTypeId];
                        $methods[] = __($method);

                        $giftCardTenderId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
                        if ($giftCardTenderId == $tenderTypeId) {
                            $this->giftCardAmount = $line->getAmount();
                        }

                        $loyaltyTenderId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_LOYALTYPOINTS_TENDER_TYPE);
                        if ($loyaltyTenderId == $tenderTypeId) {
                            $this->loyaltyPointAmount = $this->formatLoyaltyPoints($line->getAmount());
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
     * @return string
     */
    public function formatLoyaltyPoints($loyaltyPoints)
    {
        return number_format((float)$loyaltyPoints, 2, '.', '');
    }
}
