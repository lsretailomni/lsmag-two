<?php

namespace Ls\Webhooks\Test\Api;

use Ls\Core\Model\LSR;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\TestCase\WebapiAbstract;

abstract class AbstractWebhookTest extends WebapiAbstract
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /** @var string */
    protected $productSku;

    /** @var string */
    protected $email;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productSku    = (defined('WEB_API_TEST_PRODUCT_SKU')) ? WEB_API_TEST_PRODUCT_SKU : '40000';
        $this->email         = (defined('WEB_API_TEST_EMAIL')) ?
            WEB_API_TEST_EMAIL : 'testcustomer@example.com';
    }

    /**
     * Get or create a product by SKU.
     *
     * @return \Magento\Catalog\Model\Product
     * @throws \Exception
     */
    protected function getOrCreateProduct()
    {
        try {
            return $this->objectManager->get(ProductRepositoryInterface::class)->get($this->productSku);
        } catch (NoSuchEntityException $e) {
            return $this->createProduct();
        }
    }

    /**
     * Get or create a customer by email.
     *
     * @return Customer
     */
    protected function getOrCreateCustomer()
    {
        try {
            return $this->objectManager->get(CustomerRepositoryInterface::class)->get($this->email);
        } catch (NoSuchEntityException $e) {
            return $this->createCustomer();
        }
    }

    /**
     * Get or create an order by increment ID.
     *
     * @param $incrementId
     * @param $documentId
     * @param $customer
     * @param $product
     * @param $isShipping
     * @return OrderInterface
     * @throws LocalizedException
     */
    protected function getOrCreateOrder(
        $incrementId,
        $documentId,
        $customer,
        $product,
        $isShipping = true,
        $isOffline = true,
        $qty = 1
    ) {
        try {
            return $this->objectManager->get(OrderRepositoryInterface::class)->get($incrementId);
        } catch (NoSuchEntityException $e) {
            return $this->createOrder($incrementId, $documentId, $customer, $product, $isShipping, $isOffline, $qty);
        }
    }

    /**
     * Create a simple product
     *
     * @return \Magento\Catalog\Model\Product
     * @throws \Exception
     */
    private function createProduct()
    {
        /** @var ProductFactory $productFactory */
        $productFactory = $this->objectManager->get(ProductFactory::class);
        $product        = $productFactory->create();
        $product->setSku($this->productSku)
            ->setName('Test Product')
            ->setPrice(100)
            ->setAttributeSetId(4) // Default attribute set
            ->setStatus(1) // Enabled
            ->setVisibility(4) // Catalog, Search
            ->setTypeId('simple')
            ->setStockData(['qty' => 10000, 'is_in_stock' => 1])
            ->setCustomAttribute('unit_of_measure', 'PCS')
            ->setCustomAttribute(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $this->productSku)
            ->save();

        return $product;
    }

    /**
     * Create Customer
     *
     * @return \Magento\Customer\Model\Customer
     */
    private function createCustomer()
    {
        /** @var CustomerFactory $customerFactory */
        $customerFactory = $this->objectManager->get(CustomerFactory::class);
        $customer        = $customerFactory->create();
        $customer->setWebsiteId(1)
            ->setEmail($this->email)
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setPassword('password')
            ->save();

        return $this->objectManager->get(CustomerRepositoryInterface::class)->get($this->email);
    }

    /**
     * Create an order with the unique document ID.
     *
     * @param string $incrementId
     * @param string $documentId
     * @param Customer $customer
     * @param Product $product
     * @param bool $isShipping
     * @param bool $isOffline
     * @return OrderInterface
     */
    private function createOrder(
        string $incrementId,
        string $documentId,
        $customer,
        $product,
        $isShipping = true,
        $isOffline = true,
        $qty = 1
    ) {
        $quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class);
        $quote->setStoreId(1)
            ->setCustomerIsGuest(false)
            ->setCustomer($customer);

        // Load region dynamically
        $regionFactory = $this->objectManager->get(\Magento\Directory\Model\RegionFactory::class);
        $region        = $regionFactory->create()->loadByCode('CA', 'US'); // Example for California, USA

        // Add product to quote
        $quoteItem = $quote->addProduct($product, $qty);

        // Set billing and shipping addresses
        $addressData     = [
            'firstname'  => 'John',
            'lastname'   => 'Doe',
            'street'     => '123 Test St',
            'city'       => 'Test City',
            'region_id'  => $region->getId(),
            'country_id' => 'US',
            'region'     => 'California',
            'postcode'   => '90001',
            'telephone'  => '1234567890',
        ];
        $billingAddress  = $this->objectManager->create(\Magento\Quote\Model\Quote\Address::class,
            ['data' => $addressData]);
        $shippingAddress = clone $billingAddress;

        $quote->setBillingAddress($billingAddress);
        $quote->setShippingAddress($shippingAddress);
        $shippingAddress->setQuote($quote);
        $shippingQuoteRate = $this->objectManager->create(\Magento\Quote\Model\Quote\Address\Rate::class);
        if ($isShipping) {
            $shippingQuoteRate->setCarrier('flatrate_flatrate')
                ->setCarrierTitle('Flat Rate')
                ->setCode('flatrate_flatrate')
                ->setMethod('flatrate_flatrate')
                ->setPrice(5)
                ->setMethodTitle('Flatrate Shipping');
            $shippingAddress->addShippingRate($shippingQuoteRate);
            $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        } else {
            $shippingQuoteRate->setCarrier('clickandcollect_clickandcollect')
                ->setCarrierTitle('Click And Collect')
                ->setCode('clickandcollect_clickandcollect')
                ->setMethod('clickandcollect_clickandcollect')
                ->setPrice(0)
                ->setMethodTitle('Click And Collect');
            $shippingAddress->addShippingRate($shippingQuoteRate);
            $quote->getShippingAddress()->setShippingMethod('clickandcollect_clickandcollect');
        }

        // Set payment method
        if ($isOffline) {
            $quote->getPayment()->setMethod('checkmo');
        } else {
            $quote->getPayment()->setMethod('braintree');
            $quote->getPayment()->setAdditionalInformation('payment_method_nonce', 'fake-valid-nonce');
        }

        // Recalculate totals
        $quote->setInventoryProcessed(false); // Prevents inventory processing
        if ($isShipping) {
            $quote->getShippingAddress()->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod('flatrate_flatrate');
        } else {
            $quote->getShippingAddress()->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod('clickandcollect_clickandcollect');
        }
        // Apply zero tax to each item
        foreach ($quote->getAllItems() as $item) {
            $item->setTaxAmount(0);
            $item->setBaseTaxAmount(0);
            $item->setDiscountAmount(0);
            $item->setBaseDiscountAmount(0);
        }
        $quote->collectTotals();
        $quote->save();
        // Convert quote to order
        $quoteManagement = $this->objectManager->create(\Magento\Quote\Model\QuoteManagement::class);
        $quote->setBaseDiscountAmount(0);
        $quote->setDiscountAmount(0);
        $order           = $quoteManagement->submit($quote);

        // Set a custom increment ID and document ID
        $order->setIncrementId($incrementId)->setDocumentId($documentId)->save();

        return $order;
    }
}
