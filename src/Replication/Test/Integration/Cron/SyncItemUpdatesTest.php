<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use Ls\Core\Model\LSR;
use Ls\Replication\Cron\AttributesCreateTask;
use Ls\Replication\Cron\CategoryCreateTask;
use Ls\Replication\Cron\ProductCreateTask;
use Ls\Replication\Cron\ReplEcommAttributeOptionValueTask;
use Ls\Replication\Cron\ReplEcommAttributeTask;
use Ls\Replication\Cron\ReplEcommAttributeValueTask;
use Ls\Replication\Cron\ReplEcommBarcodesTask;
use Ls\Replication\Cron\ReplEcommExtendedVariantsTask;
use Ls\Replication\Cron\ReplEcommHierarchyLeafTask;
use Ls\Replication\Cron\ReplEcommHierarchyNodeTask;
use Ls\Replication\Cron\ReplEcommImageLinksTask;
use Ls\Replication\Cron\ReplEcommInventoryStatusTask;
use Ls\Replication\Cron\ReplEcommItemsTask;
use Ls\Replication\Cron\ReplEcommItemUnitOfMeasuresTask;
use Ls\Replication\Cron\ReplEcommItemVariantRegistrationsTask;
use Ls\Replication\Cron\ReplEcommItemVariantsTask;
use Ls\Replication\Cron\ReplEcommPricesTask;
use Ls\Replication\Cron\ReplEcommUnitOfMeasuresTask;
use Ls\Replication\Cron\ReplEcommVendorTask;
use Ls\Replication\Test\Fixture\FlatDataReplication;
use Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;

/**
 * @magentoAppArea crontab
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
#[
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommAttributeTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommAttributeOptionValueTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommAttributeValueTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommExtendedVariantsTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemVariantsTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommUnitOfMeasuresTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemUnitOfMeasuresTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommVendorTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommHierarchyNodeTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommHierarchyLeafTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemsTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommBarcodesTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemVariantRegistrationsTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommPricesTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommImageLinksTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommInventoryStatusTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    )
]
class SyncItemUpdatesTest extends AbstractTask
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @magentoDbIsolation enabled
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
        Config(
            LSR::SC_REPLICATION_HIERARCHY_CODE,
            AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID,
            'store',
            'default'
        ),
        Config(LSR::SC_REPLICATION_PRODUCT_BATCHSIZE, 5, 'store', 'default')
    ]
    public function testExecute()
    {
        $storeId           = $this->storeManager->getStore()->getId();
        $this->cron->store = $this->storeManager->getStore();
        $this->executePreReqCrons();

        $this->updateAllRelevantItemRecords(
            1,
            [
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
            ]
        );

        $this->executeUntilReady(ProductCreateTask::class, [
            LSR::SC_SUCCESS_CRON_PRODUCT
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_PRODUCT,
            ],
            $storeId
        );

        $configurableProduct = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '',
            '',
            $storeId
        );

        $simpleProduct = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
            '',
            '',
            $storeId
        );
    }

    public function executePreReqCrons()
    {
        $this->executeUntilReady(AttributesCreateTask::class, [
            LSR::SC_SUCCESS_CRON_ATTRIBUTE,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT
        ]);

        $this->executeUntilReady(CategoryCreateTask::class, [
            LSR::SC_SUCCESS_CRON_CATEGORY
        ]);
        $this->updateAllRelevantItemRecords();
    }

    public function executeUntilReady($cronClass, $successStatus)
    {
        for ($i = 0; $i < 4; $i++) {
            $cron = $this->objectManager->create($cronClass);
            $cron->execute();

            if ($this->isReady($successStatus, $this->storeManager->getStore()->getId())) {
                break;
            }
        }
    }

    public function isReady($successStatuses, $scopeId)
    {
        $status = true;

        foreach ($successStatuses as $successStatus) {
            $status =
                $status && $this->lsr->getConfigValueFromDb($successStatus, ScopeInterface::SCOPE_STORES, $scopeId) &&
                $successStatus !== LSR::SC_SUCCESS_CRON_PRODUCT;
        }
        return $status;
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
