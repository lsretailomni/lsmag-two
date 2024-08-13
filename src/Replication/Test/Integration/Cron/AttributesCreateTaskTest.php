<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use Ls\Core\Model\LSR;
use Ls\Replication\Api\ReplAttributeRepositoryInterface;
use Ls\Replication\Cron\AttributesCreateTask;
use Ls\Replication\Cron\ReplEcommAttributeTask;
use Ls\Replication\Cron\ReplEcommAttributeValueTask;
use Ls\Replication\Cron\ReplEcommExtendedVariantsTask;
use Ls\Replication\Cron\ReplEcommItemVariantsTask;
use Ls\Replication\Cron\ReplEcommUnitOfMeasuresTask;
use Ls\Replication\Cron\ReplEcommVendorTask;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Replication\Test\Fixture\FlatDataReplication;
use Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Catalog\Model\Product;
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
class AttributesCreateTaskTest extends TestCase
{
    /** @var ObjectManagerInterface */
    public $objectManager;

    public $cron;

    public $lsr;

    public $storeManager;

    public $replicationHelper;

    public $eavConfig;

    public $replAttributeRepositoryInterface;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager     = Bootstrap::getObjectManager();
        $this->cron              = $this->objectManager->create(AttributesCreateTask::class);
        $this->lsr               = $this->objectManager->create(\Ls\Core\Model\Lsr::class);
        $this->storeManager      = $this->objectManager->get(StoreManagerInterface::class);
        $this->replicationHelper = $this->objectManager->get(ReplicationHelper::class);
        $this->eavConfig         = $this->objectManager->get(\Magento\Eav\Model\Config::class);
        $this->replAttributeRepositoryInterface = $this->objectManager->get(ReplAttributeRepositoryInterface::class);
    }

    /**
     * @magentoDbIsolation disabled
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
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommAttributeTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommAttributeValueTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommExtendedVariantsTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemVariantsTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommUnitOfMeasuresTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommVendorTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        )
    ]
    public function testExecute()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->cron->execute();
        }
        $storeId = $this->storeManager->getStore()->getId();

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_ATTRIBUTE,
                LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
                LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT
            ],
            $storeId
        );

        $this->assertAttributesCreated(
            [
                LSR::LS_VENDOR_ATTRIBUTE,
                LSR::LS_UOM_ATTRIBUTE,
                $this->replicationHelper->formatAttributeCode(LSR::LS_STANDARD_VARIANT_ATTRIBUTE_CODE),
                $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_HARD_ATTRIBUTE),
                $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE)
            ]
        );
    }

    /**
     * @magentoDbIsolation disabled
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
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website')
    ]
    public function testSoftAttributeRemoval()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $filters = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'Code', 'value' => AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE, 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        $replAttribute =  current($this->replAttributeRepositoryInterface->getList($criteria)->getItems());

        if ($replAttribute) {
            $this->replAttributeRepositoryInterface->save(
                $replAttribute->addData(['is_updated' => 1, 'IsDeleted' => 1])
            );

            $this->cron->execute();

            $this->eavConfig->clear();
            $eavAttribute  = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE)
            );

            $isVisibleOnFront = $eavAttribute->getData('is_visible_on_front');

            $this->assertEquals(0, $isVisibleOnFront);
        }
    }

    public function assertCronSuccess($cronConfigs, $storeId)
    {
        foreach ($cronConfigs as $config) {
            $this->assertTrue((bool)$this->lsr->getConfigValueFromDb(
                $config,
                ScopeInterface::SCOPE_STORES,
                $storeId
            ));
        }
    }

    public function assertAttributesCreated($attributes)
    {
        foreach ($attributes as $attribute) {
            $eavAttribute  = $this->eavConfig->getAttribute(Product::ENTITY, $attribute);

            $this->assertNotNull($eavAttribute);
            $this->assertNotEmpty($eavAttribute->getSource()->getAllOptions());
        }
    }
}
