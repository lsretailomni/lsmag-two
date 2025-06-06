<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ReplEcommPricesTask;
use \Ls\Replication\Cron\ResetReplPriceStatusTask;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ResetReplPriceStatusTaskTest extends TestCase
{
    public $objectManager;

    public $cron;

    public $lsr;

    public $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->cron          = $this->objectManager->create(ResetReplPriceStatusTask::class);
        $this->lsr           = $this->objectManager->create(\Ls\Core\Model\Lsr::class);
        $this->storeManager  = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommPricesTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, '2023.0.0', 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, '2023.0.0', 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE)
    ]
    public function testExecuteOld()
    {
        list($fullReplicationPriceStatus1,
            $fullReplicationPriceConfigPath1,
            $fullReplicationPriceMaxKey1
            ) = $this->getRequiredValues();
        $this->assertNotNull($fullReplicationPriceStatus1);
        $this->assertNotNull($fullReplicationPriceConfigPath1);
        $this->assertNotNull($fullReplicationPriceMaxKey1);
        $this->cron->execute();
        list($fullReplicationPriceStatus2,
            $fullReplicationPriceConfigPath2,
            $fullReplicationPriceMaxKey2
            ) = $this->getRequiredValues();
        $this->assertTrue($fullReplicationPriceStatus2 == "0");
        $this->assertTrue($fullReplicationPriceConfigPath2 == "0");
        $this->assertTrue($fullReplicationPriceMaxKey2 == "0");
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommPricesTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
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
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE)
    ]
    public function testExecuteNew()
    {
        list($fullReplicationStatus1,
            $fullReplicationConfigPath1,
            $fullReplicationMaxKey1
            ) = $this->getRequiredValues();
        $this->assertNotNull($fullReplicationStatus1);
        $this->assertNotNull($fullReplicationConfigPath1);
        $this->assertNotNull($fullReplicationMaxKey1);
        $this->cron->execute();
        list($fullReplicationStatus2,
            $fullReplicationConfigPath2,
            $fullReplicationMaxKey2
            ) = $this->getRequiredValues();
        $this->assertNotNull($fullReplicationStatus2);
        $this->assertNotNull($fullReplicationConfigPath2);
        $this->assertNotNull($fullReplicationMaxKey2);
    }

    public function getRequiredValues()
    {
        $fullReplicationPriceStatus = $this->lsr->getConfigValueFromDb(
            ReplEcommPricesTask::CONFIG_PATH_STATUS,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getWebsite()->getId()
        );
        $fullReplicationPriceConfigPath = $this->lsr->getConfigValueFromDb(
            ReplEcommPricesTask::CONFIG_PATH,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getWebsite()->getId()
        );
        $fullReplicationPriceMaxKey = $this->lsr->getConfigValueFromDb(
            ReplEcommPricesTask::CONFIG_PATH_MAX_KEY,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getWebsite()->getId()
        );
        return [
            $fullReplicationPriceStatus,
            $fullReplicationPriceConfigPath,
            $fullReplicationPriceMaxKey
        ];
    }
}
