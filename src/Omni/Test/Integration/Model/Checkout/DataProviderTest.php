<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Model\Checkout;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use Ls\Omni\Test\Fixture\FlatDataReplication;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Model\Checkout\DataProvider;
use Ls\Replication\Cron\ReplEcommStoresTask;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
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

    public $registry;
    public $customerSession;
    public $checkoutSession;
    public $contactHelper;
    public $dataProvider;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->dataProvider    = $this->objectManager->get(DataProvider::class);
        $this->registry        = $this->objectManager->get(Registry::class);
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
        $this->contactHelper   = $this->objectManager->get(ContactHelper::class);
        $this->checkoutSession = $this->objectManager->get(CheckoutSession::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::SC_CLICKCOLLECT_ENABLED, 1, 'store', 'default'),
        Config(DataProvider::XPATH_MAPS_API_KEY, null, 'store', 'default'),
        Config(DataProvider::XPATH_DEFAULT_LATITUDE, '52.1349', 'store', 'default'),
        Config(DataProvider::XPATH_DEFAULT_LONGITUDE, '-0.04615', 'store', 'default'),
        Config(DataProvider::XPATH_DEFAULT_ZOOM, '6', 'store', 'default'),
        Config(DataProvider::XPATH_CHECKOUT_ITEM_AVAILABILITY, '0', 'store', 'default'),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommStoresTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ],
            as: 'stores'
        ),
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
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])

    ]
    public function testGetConfig()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $result = $this->dataProvider->getConfig();

        $this->assertNotNull($result);
        $this->assertArrayHasKey('shipping', $result);
        $this->assertArrayHasKey('shipping', $result);
        $this->assertArrayHasKey('pickup_date_timeslots', $result['shipping']);
    }
}
