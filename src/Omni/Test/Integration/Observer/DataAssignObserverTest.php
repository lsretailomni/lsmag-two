<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Test\Fixture\ApplyLoyaltyPointsInCartFixture;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Observer\DataAssignObserver;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Fixture\AppArea;

class DataAssignObserverTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var mixed
     */
    public $request;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var mixed
     */
    public $registry;

    /**
     * @var mixed
     */
    public $customerSession;

    /**
     * @var mixed
     */
    public $checkoutSession;

    /**
     * @var mixed
     */
    public $controllerAction;

    /**
     * @var mixed
     */
    public $basketHelper;

    /**
     * @var mixed
     */
    public $eventManager;

    /**
     * @var DataAssignObserver
     */
    public $dataAssignObserver;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var QuoteManagement
     */
    public $quoteManagement;

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
        $this->objectManager               = Bootstrap::getObjectManager();
        $this->request                     = $this->objectManager->get(HttpRequest::class);
        $this->fixtures                    = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->registry                    = $this->objectManager->get(Registry::class);
        $this->customerSession             = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession             = $this->objectManager->get(CheckoutSession::class);
        $this->controllerAction            = $this->objectManager->get(Action::class);
        $this->basketHelper                = $this->objectManager->get(BasketHelper::class);
        $this->eventManager                = $this->objectManager->create(ManagerInterface::class);
        $this->dataAssignObserver          = $this->objectManager->get(DataAssignObserver::class);
        $this->quoteManagement             = $this->objectManager->get(QuoteManagement::class);
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
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, self::LICENSE, 'website'),
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
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180'
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        )
    ]
    /**
     * Verify exception throw for order with click and collect payment method and
     * flatrate shipping.
     */
    public function testOrderUpdatesWithException()
    {
        $address = $this->fixtures->get('address');
        $quote   = $this->fixtures->get('cart1');

        $this->checkoutSession->setQuoteId($quote->getId());
        $quoteShippingAddress = $this->addressInterfaceFactory->create();
        $quoteShippingAddress->importCustomerAddressData(
            $this->addressRespositoryInterface->getById($address->getId())
        );

        $quote->setShippingAddress($quoteShippingAddress);
        $quote->setBillingAddress($quoteShippingAddress);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->setPickupStore(AbstractIntegrationTest::STORE_PICKUP);
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->getPayment()->setMethod("ls_payment_method_pay_at_store");

        $this->expectException(ValidatorException::class);

        $order = $this->quoteManagement->submit($quote);

        $this->basketHelper->setOneListCalculationInCheckoutSession(null);
        $quote->delete();
        $this->checkoutSession->clearQuote();
    }
}
