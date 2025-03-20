<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ReplEcommDiscountsTask;
use \Ls\Replication\Cron\ResetReplDiscountStatusTask;
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
class ResetReplDiscountStatusTaskTest extends TestCase
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
        $this->cron          = $this->objectManager->create(ResetReplDiscountStatusTask::class);
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
                'job_url' => ReplEcommDiscountsTask::class,
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
        list($fullReplicationDiscountStatus1,
            $fullReplicationDiscountConfigPath1,
            $fullReplicationDiscountMaxKey1
            ) = $this->getRequiredValues();
        $this->assertNotNull($fullReplicationDiscountStatus1);
        $this->assertNotNull($fullReplicationDiscountConfigPath1);
        $this->assertNotNull($fullReplicationDiscountMaxKey1);
        $this->cron->execute();
        list($fullReplicationDiscountStatus2,
            $fullReplicationDiscountConfigPath2,
            $fullReplicationDiscountMaxKey2
            ) = $this->getRequiredValues();
        $this->assertTrue($fullReplicationDiscountStatus2 == "0");
        $this->assertTrue($fullReplicationDiscountConfigPath2 == "0");
        $this->assertTrue($fullReplicationDiscountMaxKey2 == "0");
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommDiscountsTask::class,
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
        $this->assertNull($fullReplicationStatus1);
        $this->assertNull($fullReplicationConfigPath1);
        $this->assertNull($fullReplicationMaxKey1);
        $this->cron->execute();
        list($fullReplicationStatus2,
            $fullReplicationConfigPath2,
            $fullReplicationMaxKey2
            ) = $this->getRequiredValues();
        $this->assertNull($fullReplicationStatus2);
        $this->assertNull($fullReplicationConfigPath2);
        $this->assertNull($fullReplicationMaxKey2);
    }

    public function getRequiredValues()
    {
        $fullReplicationDiscountStatus = $this->lsr->getConfigValueFromDb(
            ReplEcommDiscountsTask::CONFIG_PATH_STATUS,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getWebsite()->getId()
        );
        $fullReplicationDiscountConfigPath = $this->lsr->getConfigValueFromDb(
            ReplEcommDiscountsTask::CONFIG_PATH,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getWebsite()->getId()
        );
        $fullReplicationDiscountMaxKey = $this->lsr->getConfigValueFromDb(
            ReplEcommDiscountsTask::CONFIG_PATH_MAX_KEY,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getWebsite()->getId()
        );
        return [
            $fullReplicationDiscountStatus,
            $fullReplicationDiscountConfigPath,
            $fullReplicationDiscountMaxKey
        ];
    }
}
