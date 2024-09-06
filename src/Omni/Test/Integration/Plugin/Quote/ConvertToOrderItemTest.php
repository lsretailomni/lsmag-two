<?php

namespace Ls\Omni\Test\Integration\Plugin\Quote;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Fixture\ApplyLoyaltyPointsInCartFixture;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;

class ConvertToOrderItemTest extends AbstractIntegrationTest
{
    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var QuoteManagement
     */
    public $quoteManagement;

    /**
     * @var mixed
     */
    public $eventManager;

    /**
     * @var mixed
     */
    public $customerSession;

    /**
     * @var mixed
     */
    public $checkoutSession;

    /**
     * @var AddressInterfaceFactory
     */
    public $addressInterfaceFactory;

    /**
     * @var AddressRepositoryInterface
     */
    public $addressRespositoryInterface;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager               = Bootstrap::getObjectManager();
        $this->fixtures                    = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->quoteManagement             = $this->objectManager->get(QuoteManagement::class);
        $this->customerSession             = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession             = $this->objectManager->get(CheckoutSession::class);
        $this->eventManager                = $this->objectManager->create(ManagerInterface::class);
        $this->addressInterfaceFactory     = $this->objectManager->create(AddressInterfaceFactory::class);
        $this->addressRespositoryInterface = $this->objectManager->create(AddressRepositoryInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
        DataFixture(ApplyLoyaltyPointsInCartFixture::class, ['cart' => '$cart1$']),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        )
    ]
    public function testAroundGet()
    {
        $customer        = $this->fixtures->get('customer');
        $quote           = $this->fixtures->get('cart1');
        $address         = $this->fixtures->get('address');
        $reservedOrderId = 'test01';
        $notExpected     = 0.00;

        $quoteShippingAddress = $this->addressInterfaceFactory->create();
        $quoteShippingAddress->importCustomerAddressData(
            $this->addressRespositoryInterface->getById($address->getId())
        );

        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($quote->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $quote->getAllVisibleItems()]);
        $quote->load($reservedOrderId, 'reserved_order_id');

        $quote->setShippingAddress($quoteShippingAddress);
        $quote->setBillingAddress($quoteShippingAddress);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->getPayment()->setMethod('checkmo');
        $quote->save();

        $order = $this->quoteManagement->submit($quote);

        foreach ($order->getAllVisibleItems() as $item) {
            $this->assertGreaterThan($notExpected, $item->setLsDiscountAmount());
        }
    }
}
