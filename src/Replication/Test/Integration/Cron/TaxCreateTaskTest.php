<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use Ls\Core\Model\LSR;
use Ls\Replication\Cron\ReplEcommCountryCodeTask;
use Ls\Replication\Cron\ReplEcommStoresTask;
use Ls\Replication\Cron\ReplEcommTaxSetupTask;
use Ls\Replication\Cron\TaxRulesCreateTask;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Replication\Test\Fixture\FlatDataReplication;
use Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\ObjectManagerInterface;
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
#[
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommTaxSetupTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommCountryCodeTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommStoresTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    )
]
class TaxCreateTaskTest extends TestCase
{
    /** @var ObjectManagerInterface */
    public $objectManager;

    public $cron;

    public $lsr;

    public $storeManager;

    public $replicationHelper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager                               = Bootstrap::getObjectManager();
        $this->cron                                        = $this->objectManager->create(TaxRulesCreateTask::class);
        $this->lsr                                         = $this->objectManager->create(\Ls\Core\Model\Lsr::class);
        $this->storeManager                                = $this->objectManager->get(StoreManagerInterface::class);
        $this->replicationHelper                           = $this->objectManager->get(ReplicationHelper::class);
    }

    /**
     * @magentoAppIsolation enabled
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
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE)
    ]
    public function testExecute()
    {
        $this->executeUntilReady();
        $storeId = $this->storeManager->getStore()->getId();

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_TAX_RULES,
            ],
            $storeId
        );
    }

    public function executeUntilReady()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->cron->execute();

            if ($this->isReady($this->storeManager->getStore()->getId())) {
                break;
            }
        }
    }

    public function isReady($scopeId)
    {
        $cronTaxRules                = $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_TAX_RULES,
            ScopeInterface::SCOPE_STORES,
            $scopeId
        );
        return $cronTaxRules;
    }

    public function assertCronSuccess($cronConfigs, $storeId, $status = true)
    {
        foreach ($cronConfigs as $config) {
            if (!$status) {
                $this->assertFalse((bool)$this->lsr->getConfigValueFromDb(
                    $config,
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ));
            } else {
                $this->assertTrue((bool)$this->lsr->getConfigValueFromDb(
                    $config,
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ));
            }
        }
    }
}
