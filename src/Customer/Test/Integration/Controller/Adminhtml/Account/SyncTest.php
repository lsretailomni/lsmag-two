<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Controller\Adminhtml\Account;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\Manager;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

class SyncTest extends AbstractBackendController
{
    public $objectManager;
    public $contactHelper;
    public $fixtures;
    public $messageManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource   = 'Magento_Backend::admin';
        $this->uri        = 'backend/lscustomer/account/sync';
        $this->httpMethod = HttpRequest::METHOD_GET;
        parent::setUp();

        $this->objectManager  = Bootstrap::getObjectManager();
        $this->contactHelper  = $this->objectManager->get(ContactHelper::class);
        $this->fixtures       = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->messageManager = $this->objectManager->get(Manager::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'random_email' => 1,
                'lsr_username' => null,
                'lsr_id'       => null,
                'lsr_cardid'   => null
            ],
            'customer'
        )
    ]
    public function testCustomerSyncForNonExistingCustomerInCentral(): void
    {
        $customer = $this->fixtures->get('customer');
        $this->getRequest()->setParams(['customer_id' => $customer->getId()]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('backend/lscustomer/account/sync');

        $updatedCustomer = $this->contactHelper->getCustomerByEmail($customer->getEmail());
        $this->assertNotNull($updatedCustomer->getData('lsr_username'));
        $this->assertNotNull($updatedCustomer->getData('lsr_id'));
        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => null,
                'lsr_id'       => null,
                'lsr_cardid'   => null
            ],
            'customer'
        )
    ]
    public function testCustomerSyncForExistentCustomerInCentral(): void
    {
        $customer = $this->fixtures->get('customer');
        $this->getRequest()->setParams(['customer_id' => $customer->getId()]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('backend/lscustomer/account/sync');

        $updatedCustomer = $this->contactHelper->getCustomerByEmail($customer->getEmail());
        $this->assertNull($updatedCustomer->getData('lsr_username'));
        $this->assertNull($updatedCustomer->getData('lsr_id'));
        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => null,
                'lsr_id'       => null,
                'lsr_cardid'   => null
            ],
            'customer'
        )
    ]
    public function testCustomerSyncWithLsrDown(): void
    {
        $customer = $this->fixtures->get('customer');
        $this->getRequest()->setParams(['customer_id' => $customer->getId()]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('backend/lscustomer/account/sync');
        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
    }
}
