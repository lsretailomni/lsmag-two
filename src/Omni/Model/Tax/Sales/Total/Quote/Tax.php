<?php
namespace Ls\Omni\Model\Tax\Sales\Total\Quote;


use Magento\Customer\Api\Data\AddressInterfaceFactory as CustomerAddressFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory as CustomerAddressRegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;


class Tax extends \Magento\Tax\Model\Sales\Total\Quote\Tax
{
    const CONFIG_XML_PATH_HANDLING_FEE_TAX_CLASS = 'tax/classes/handling_fee_tax_class';

    const ITEM_TYPE_HANDLING_FEE = 'handling_fee';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Tax constructor.
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory
     * @param \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory
     * @param CustomerAddressFactory $customerAddressFactory
     * @param CustomerAddressRegionFactory $customerAddressRegionFactory
     * @param \Magento\Tax\Helper\Data $taxData
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressRegionFactory $customerAddressRegionFactory,
        \Magento\Tax\Helper\Data $taxData,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct(
            $taxConfig,
            $taxCalculationService,
            $quoteDetailsDataObjectFactory,
            $quoteDetailsItemDataObjectFactory,
            $taxClassKeyDataObjectFactory,
            $customerAddressFactory,
            $customerAddressRegionFactory,
            $taxData
        );

        $this->scopeConfig = $scopeConfig;
    }


    /**
     * Call tax calculation service to get tax details on the quote and items
     *
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @param bool $useBaseCurrency
     * @return \Magento\Tax\Api\Data\TaxDetailsInterface
     */
    protected function getQuoteTaxDetails($shippingAssignment, $total, $useBaseCurrency)
    {
        $address = $shippingAssignment->getShipping()->getAddress();
        //Setup taxable items
        $priceIncludesTax = $this->_config->priceIncludesTax($address->getQuote()->getStore());
        $itemDataObjects = $this->mapItems($shippingAssignment, $priceIncludesTax, $useBaseCurrency);

        //Add shipping
        $shippingDataObject = $this->getShippingDataObject($shippingAssignment, $total, $useBaseCurrency);
        if ($shippingDataObject != null) {
            $itemDataObjects[] = $shippingDataObject;
        }

        // begin override, add payment handling fee
        $handlingFeeDataObject = $this->getHandlingFeeDataObject($shippingAssignment, $total, $useBaseCurrency);
        if ($handlingFeeDataObject != null) {
            $itemDataObjects[] = $handlingFeeDataObject;
        }
        // end override

        //process extra taxable items associated only with quote
        $quoteExtraTaxables = $this->mapQuoteExtraTaxables(
            $this->quoteDetailsItemDataObjectFactory,
            $address,
            $useBaseCurrency
        );

        if (!empty($quoteExtraTaxables)) {
            $itemDataObjects = array_merge($itemDataObjects, $quoteExtraTaxables);
        }
        //Preparation for calling taxCalculationService
        $quoteDetails = $this->prepareQuoteDetails($shippingAssignment, $itemDataObjects);

        $taxDetails = $this->taxCalculationService
            ->calculateTax($quoteDetails, $address->getQuote()->getStore()->getStoreId());

        return $taxDetails;
    }

    /**
     * Get handling fee data object
     *
     * @param $shippingAssignment
     * @param $total
     * @param $useBaseCurrency
     * @return mixed
     */
    protected function getHandlingFeeDataObject($shippingAssignment, $total, $useBaseCurrency)
    {
        $store = $shippingAssignment->getShipping()->getAddress()->getQuote()->getStore();
        $itemDataObject = $this->quoteDetailsItemDataObjectFactory->create()
            ->setType(self::ITEM_TYPE_HANDLING_FEE)
            ->setCode(self::ITEM_TYPE_HANDLING_FEE)
            ->setQuantity(1);

        if ($useBaseCurrency) {
            $itemDataObject->setUnitPrice($total->getBaseHandlingFeeAmount());
        } else {
            $itemDataObject->setUnitPrice($total->getHandlingFeeAmount());
        }
        $itemDataObject->setTaxClassKey(
            $this->taxClassKeyDataObjectFactory->create()
                ->setType(TaxClassKeyInterface::TYPE_ID)
                ->setValue($this->getHandlingFeeTaxClass($store))
        );
        $itemDataObject->setIsTaxIncluded(false);

        return $itemDataObject;
    }

    /**
     * Get handling fee tax class
     *
     * @param null $store
     * @return int
     */
    protected function getHandlingFeeTaxClass($store = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_HANDLING_FEE_TAX_CLASS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Collect tax totals for quote address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $this->clearValues($total);
        $this->clearValues($total);
        if (!$shippingAssignment->getItems()) {
            return $this;
        }

        $baseTaxDetails = $this->getQuoteTaxDetails($shippingAssignment, $total, true);
        $taxDetails = $this->getQuoteTaxDetails($shippingAssignment, $total, false);

        //Populate address and items with tax calculation results
        $itemsByType = $this->organizeItemTaxDetailsByType($taxDetails, $baseTaxDetails);
        if (isset($itemsByType[self::ITEM_TYPE_PRODUCT])) {
            $this->processProductItems($shippingAssignment, $itemsByType[self::ITEM_TYPE_PRODUCT], $total);
        }

        if (isset($itemsByType[self::ITEM_TYPE_SHIPPING])) {
            $shippingTaxDetails = $itemsByType[self::ITEM_TYPE_SHIPPING][self::ITEM_CODE_SHIPPING][self::KEY_ITEM];
            $baseShippingTaxDetails =
                $itemsByType[self::ITEM_TYPE_SHIPPING][self::ITEM_CODE_SHIPPING][self::KEY_BASE_ITEM];
            $this->processShippingTaxInfo($shippingAssignment, $total, $shippingTaxDetails, $baseShippingTaxDetails);
        }

        // Override, set handling fee
        if (isset($itemsByType[self::ITEM_TYPE_HANDLING_FEE])) {
            $handlingFeeTaxDetails = $itemsByType[self::ITEM_TYPE_HANDLING_FEE][self::ITEM_TYPE_HANDLING_FEE][self::KEY_ITEM];
            $baseHandlingFeeTaxDetails =
                $itemsByType[self::ITEM_TYPE_HANDLING_FEE][self::ITEM_TYPE_HANDLING_FEE][self::KEY_BASE_ITEM];
            $this->processHandlingFeeTaxInfo($shippingAssignment, $total, $handlingFeeTaxDetails, $baseHandlingFeeTaxDetails);

            $this->setHandlingFeeInfoOnQuote($quote, $handlingFeeTaxDetails, $baseHandlingFeeTaxDetails);
        }
        //End override

        //Process taxable items that are not product or shipping
        $this->processExtraTaxables($total, $itemsByType);

        //Save applied taxes for each item and the quote in aggregation
        $this->processAppliedTaxes($total, $shippingAssignment, $itemsByType);

        if ($this->includeExtraTax()) {
            $total->addTotalAmount('extra_tax', $total->getExtraTaxAmount());
            $total->addBaseTotalAmount('extra_tax', $total->getBaseExtraTaxAmount());
        }

        return $this;
    }

    /**
     * Set handling fee on total
     *
     * @param $shippingAssignment
     * @param $total
     * @param $handlingFeeTaxDetails
     * @param $baseHandlingFeeTaxDetails
     * @return $this
     */
    protected function processHandlingFeeTaxInfo($shippingAssignment, $total, $handlingFeeTaxDetails, $baseHandlingFeeTaxDetails)
    {
        $total->setTotalAmount(self::ITEM_TYPE_HANDLING_FEE, $handlingFeeTaxDetails->getRowTotal());
        $total->setBaseTotalAmount(self::ITEM_TYPE_HANDLING_FEE, $baseHandlingFeeTaxDetails->getRowTotal());

        $total->setHandlingFeeTaxAmount($handlingFeeTaxDetails->getRowTax());
        $total->setBaseHandlingFeeTaxAmount($baseHandlingFeeTaxDetails->getRowTax());

        $total->setHandlingFeeInclTax($handlingFeeTaxDetails->getRowTotalInclTax());
        $total->setBaseHandlingFeeInclTax($baseHandlingFeeTaxDetails->getRowTotalInclTax());
        $total->setHandlingFeeTaxAmount($handlingFeeTaxDetails->getRowTax());
        $total->setBaseHandlingFeeTaxAmount($baseHandlingFeeTaxDetails->getRowTax());

        return $this;
    }

    /**
     * Set handling fee on quote
     *
     * @param $quote
     * @param $handlingFeeTaxDetails
     * @param $baseHandlingFeeTaxDetails
     */
    protected function setHandlingFeeInfoOnQuote($quote, $handlingFeeTaxDetails, $baseHandlingFeeTaxDetails)
    {
        $quote->setHandlingFeeAmount($handlingFeeTaxDetails->getRowTotal());
        $quote->setBaseHandlingFeeAmount($baseHandlingFeeTaxDetails->getRowTotal());
        $quote->setHandlingFeeInclTax($handlingFeeTaxDetails->getRowTotalInclTax());
        $quote->setBaseHandlingFeeInclTax($baseHandlingFeeTaxDetails->getPriceInclTax());
        $quote->setHandlingFeeTaxAmount($handlingFeeTaxDetails->getRowTax());
        $quote->setBaseHandlingFeeTaxAmount($baseHandlingFeeTaxDetails->getRowTax());
    }
}