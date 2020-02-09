<?php

namespace Ls\Omni\Model\Tax\Sales\Total\Quote;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Customer\Api\Data\AddressInterfaceFactory as CustomerAddressFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory as CustomerAddressRegionFactory;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Config;

class Tax extends \Magento\Tax\Model\Sales\Total\Quote\Tax
{

    /**
     * @var BasketHelper
     */
    protected $basketHelper;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * Tax constructor.
     * @param Config $taxConfig
     * @param TaxCalculationInterface $taxCalculationService
     * @param QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory
     * @param QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory
     * @param TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory
     * @param CustomerAddressFactory $customerAddressFactory
     * @param CustomerAddressRegionFactory $customerAddressRegionFactory
     * @param Data $taxData
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     */
    public function __construct(
        Config $taxConfig,
        TaxCalculationInterface $taxCalculationService,
        QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressRegionFactory $customerAddressRegionFactory,
        Data $taxData,
        BasketHelper $basketHelper,
        LoyaltyHelper $loyaltyHelper
    ) {
        $this->setCode('tax');
        $this->basketHelper  = $basketHelper;
        $this->loyaltyHelper = $loyaltyHelper;
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
    }

    /**
     * Custom Collect tax totals for quote address
     */
    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this|\Magento\Tax\Model\Sales\Total\Quote\Tax
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (isset($basketData)) {
            $total->setTaxAmount($basketData->getTotalAmount() - $basketData->getTotalNetAmount());
        }

        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     *
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (!empty($basketData)) {
            $taxAmount = $basketData->getTotalAmount() - $basketData->getTotalNetAmount();
        } else {
            $taxAmount = 0;
        }
        $result = null;
        $result = [
            'code'  => $this->getCode(),
            'title' => __('Tax (Inclusive Total)'),
            'value' => $taxAmount,
        ];
        return $result;
    }
}
