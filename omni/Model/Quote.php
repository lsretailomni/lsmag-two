<?php
/**
 * Created by PhpStorm.
 * User: florin
 * Date: 26/07/2017
 * Time: 10:54
 */

namespace Ls\Omni\Model;

use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Client\Ecommerce\Entity;

class Quote extends \Magento\Quote\Model\Quote
{
    protected $basketHelper;
    protected $totalFactory;
    protected $eventManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Framework\Message\Factory $messageFactory,
        \Magento\Sales\Model\Status\ListFactory $statusListFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\Quote\PaymentFactory $quotePaymentFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory $quotePaymentCollectionFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Quote\Model\Quote\Item\Processor $itemProcessor,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Quote\Model\Cart\CurrencyFactory $currencyFactory,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Model\Quote\TotalsReader $totalsReader,
        \Magento\Quote\Model\ShippingFactory $shippingFactory,
        \Magento\Quote\Model\ShippingAssignmentFactory $shippingAssignmentFactory,
        BasketHelper $basketHelper,
        \Magento\Quote\Model\Quote\Address\TotalFactory $totalFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $quoteValidator,
            $catalogProduct, $scopeConfig, $storeManager, $config, $quoteAddressFactory, $customerFactory,
            $groupRepository, $quoteItemCollectionFactory, $quoteItemFactory, $messageFactory, $statusListFactory,
            $productRepository, $quotePaymentFactory, $quotePaymentCollectionFactory, $objectCopyService,
            $stockRegistry, $itemProcessor, $objectFactory, $addressRepository, $criteriaBuilder, $filterBuilder,
            $addressDataFactory, $customerDataFactory, $customerRepository, $dataObjectHelper,
            $extensibleDataObjectConverter, $currencyFactory, $extensionAttributesJoinProcessor, $totalsCollector,
            $totalsReader, $shippingFactory, $shippingAssignmentFactory, $resource, $resourceCollection, $data);
        $this->basketHelper = $basketHelper;
        $this->totalFactory = $totalFactory;
        $this->eventManager = $eventManager;
    }

    public function collectTotals()
    {
        if ($this->getTotalsCollectedFlag()) {
            return $this;
        }

        // logic partly taken from Magento\Quote\Model\Quote\TotalsCollector->collect()

        /** @var \Magento\Quote\Model\Quote\Address\Total $total */
        $total = $this->totalFactory->create('Magento\Quote\Model\Quote\Address\Total');

        $this->eventManager->dispatch(
            'sales_quote_collect_totals_before',
            ['quote' => $this]
        );

        // fetch calculation via BasketHelper
        /** @var Entity\BasketCalcResponse $basketCalc */
        $basketCalc = $this->basketHelper->getOneListCalculation();

        $this->_collectItemsQtys($quote);

        // calculate totals
        // (base)GrandTotal is after tax, (base)SubTotal is before tax
        // (sub/grand)Total is in customer currency, base(Sub/Grand)total in shop currency
        // https://stackoverflow.com/questions/9704556/what-is-the-difference-between-subtotal-and-basesubtotal
        // we don't support multiple currencies yet, so base = non-base
        // in LS Mag 1, we did
        // 'currency' => $basket_calculation->getCurrencyCode()
        // 'total_amount' => $basket_calculation->getTotalAmount()
        // 'total_discount_amount' => $basket_calculation->getTotalDiscAmount()
        // 'total_net_amount' => $basket_calculation->getTotalNetAmount()
        // 'total_tax_amount' => $basket_calculation->getTotalTaxAmount()
        // 'order_base_subtotal' => $order->getBaseSubtotal()
        // 'order_discount_amount' => $order->getDiscountAmount()
        // 'order_grand_total' => $order->getGrandTotal()
        // 'order_shipping_amount' => $order->getShippingAmount()

        // TODO: add shipping
        $total->setShippingAmount($basketCalc->getShippingAmount());
        $total->setBaseShippingAmount($basketCalc->getShippingAmount());
        #$total->setShippingDescription($this->basketHelper->getShipmentFeeProduct()->getDescription());
        $total->setShippingDescription("Shipping");

        // customer currency, before tax
        $total->setSubtotal($basketCalc->getTotalNetAmount());
        // shop currency, before tax
        $total->setBaseSubtotal($basketCalc->getTotalNetAmount());

        // customer currency, with discount, before tax
        $total->setSubtotalWithDiscount($basketCalc->getTotalNetAmount()-$basketCalc->getTotalDiscAmount());
        // shop currency, with discount, before tax
        $total->setBaseSubtotalWithDiscount($basketCalc->getTotalNetAmount()-$basketCalc->getTotalDiscAmount());

        // after tax, customer currency
        $total->setGrandTotal($basketCalc->getTotalAmount());
        // after tax, shop currency
        $total->setBaseGrandTotal($basketCalc->getTotalNetAmount());


        $this->quoteValidator->validateQuoteAmount($quote, $quote->getGrandTotal());
        $this->quoteValidator->validateQuoteAmount($quote, $quote->getBaseGrandTotal());
        $this->_validateCouponCode($quote);
        $this->eventManager->dispatch(
            'sales_quote_collect_totals_after',
            ['quote' => $quote]
        );
        
        $this->addData($total->getData());

        $this->setTotalsCollectedFlag(true);
        return $this;
    }
}