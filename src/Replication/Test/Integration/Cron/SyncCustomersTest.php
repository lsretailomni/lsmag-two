<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Replication\Cron\SyncCustomers;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\Message\Manager;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class SyncCustomersTest extends TestCase
{
    public $fixtures;
    public $objectManager;
    public $cron;
    public $lsr;
    public $contactHelper;
    public $messageManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->cron          = $this->objectManager->create(SyncCustomers::class);
        $this->lsr           = $this->objectManager->create(\Ls\Core\Model\Lsr::class);
        $this->fixtures      = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->contactHelper  = $this->objectManager->get(ContactHelper::class);
        $this->messageManager = $this->objectManager->get(Manager::class);
    }

    /**
     * @magentoAppIsolation disabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE),
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
    public function testExecute()
    {
        $this->executeUntilReady();
        $customer = $this->fixtures->get('customer');
        $updatedCustomer = $this->contactHelper->getCustomerByEmail($customer->getEmail());
        $this->assertNotNull($updatedCustomer->getData('lsr_username'));
        $this->assertNotNull($updatedCustomer->getData('lsr_id'));
    }

    public function executeUntilReady()
    {
        $this->cron->execute();
    }
}
