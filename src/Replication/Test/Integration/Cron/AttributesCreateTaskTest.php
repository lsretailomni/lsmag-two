<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\Data\ReplAttributeOptionValueInterfaceFactory;
use \Ls\Replication\Api\Data\ReplExtendedVariantValueInterfaceFactory;
use \Ls\Replication\Api\Data\ReplItemVariantInterfaceFactory;
use \Ls\Replication\Api\Data\ReplUnitOfMeasureInterfaceFactory;
use \Ls\Replication\Api\Data\ReplVendorInterfaceFactory;
use \Ls\Replication\Api\ReplAttributeOptionValueRepositoryInterface;
use \Ls\Replication\Api\ReplAttributeRepositoryInterface;
use \Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use \Ls\Replication\Api\ReplItemVariantRepositoryInterface;
use \Ls\Replication\Api\ReplUnitOfMeasureRepositoryInterface;
use \Ls\Replication\Api\ReplVendorRepositoryInterface;
use \Ls\Replication\Cron\AttributesCreateTask;
use Ls\Replication\Cron\ReplLscAttributeOptionValueTask;
use Ls\Replication\Cron\ReplLscAttributeTask;
use Ls\Replication\Cron\ReplLscAttributeValueTask;
use Ls\Replication\Cron\ReplLscItemVariantTask;
use Ls\Replication\Cron\ReplLscUnitOfMeasureTask;
use Ls\Replication\Cron\ReplLscVendorTask;
use Ls\Replication\Cron\ReplLscWiExtdVariantValuesTask;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Catalog\Model\Product;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Swatches\Model\Swatch;
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
            'job_url' => ReplLscAttributeTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplLscAttributeOptionValueTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplLscAttributeValueTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplLscWiExtdVariantValuesTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplLscItemVariantTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplLscUnitOfMeasureTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplLscVendorTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    )
]
class AttributesCreateTaskTest extends TestCase
{
    public const SAMPLE_NEW_SOFT_ATTRIBUTE_OPTION_LABEL = 'Nylon';
    public const SAMPLE_NEW_HARD_ATTRIBUTE_OPTION_LABEL = '100';
    public const SAMPLE_NEW_UOM_ATTRIBUTE_OPTION_LABEL = 'TEST_UOM';
    public const SAMPLE_NEW_VENDOR_ATTRIBUTE_OPTION_LABEL = 'Test Vendor';

    public const SAMPLE_NEW_STANDARD_VARIANT_OPTION_LABEL = 'Test/Product';
    /** @var ObjectManagerInterface */
    public $objectManager;

    public $cron;

    public $lsr;

    public $storeManager;

    public $replicationHelper;

    public $eavConfig;

    public $replAttributeRepositoryInterface;

    public $serializer;

    public $replAttributeOptionValueRepositoryInterface;

    public $replAttributeOptionValueInterfaceFactory;

    public $replExtendedVariantValueInterfaceFactory;

    public $replExtendedVariantValueRepository;

    public $replUnitOfMeasureInterfaceFactory;

    public $replUnitOfMeasureRepository;

    public $replVendorInterfaceFactory;

    public $replVendorRepository;

    public $replItemVariantInterfaceFactory;

    public $replItemVariantRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager                               = Bootstrap::getObjectManager();
        $this->cron                                        = $this->objectManager->create(AttributesCreateTask::class);
        $this->lsr                                         = $this->objectManager->create(\Ls\Core\Model\Lsr::class);
        $this->storeManager                                = $this->objectManager->get(StoreManagerInterface::class);
        $this->replicationHelper                           = $this->objectManager->get(ReplicationHelper::class);
        $this->eavConfig                                   = $this->objectManager->get(\Magento\Eav\Model\Config::class);
        $this->replAttributeRepositoryInterface            = $this->objectManager->get(ReplAttributeRepositoryInterface::class);
        $this->serializer                                  = $this->objectManager->get(Json::class);
        $this->replAttributeOptionValueRepositoryInterface = $this->objectManager->get(ReplAttributeOptionValueRepositoryInterface::class);
        $this->replAttributeOptionValueInterfaceFactory    = $this->objectManager->get(ReplAttributeOptionValueInterfaceFactory::class);
        $this->replExtendedVariantValueInterfaceFactory    = $this->objectManager->get(ReplExtendedVariantValueInterfaceFactory::class);
        $this->replExtendedVariantValueRepository          = $this->objectManager->get(ReplExtendedVariantValueRepository::class);
        $this->replUnitOfMeasureInterfaceFactory           = $this->objectManager->get(ReplUnitOfMeasureInterfaceFactory::class);
        $this->replVendorInterfaceFactory                  = $this->objectManager->get(ReplVendorInterfaceFactory::class);
        $this->replItemVariantInterfaceFactory             = $this->objectManager->get(ReplItemVariantInterfaceFactory::class);
        $this->replItemVariantRepository                   = $this->objectManager->get(ReplItemVariantRepositoryInterface::class);
        $this->replUnitOfMeasureRepository                 = $this->objectManager->get(ReplUnitOfMeasureRepositoryInterface::class);
        $this->replVendorRepository                        = $this->objectManager->get(ReplVendorRepositoryInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'store', 'default'),
    ]
    public function testExecute()
    {
        $this->executeUntilReady();
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
     * @magentoDbIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'store', 'default'),
    ]
    public function testAddNewAttributeOption()
    {
        $this->executeUntilReady();
        $this->addDummySoftAttributeOptionData();
        $this->addDummyHardAttributeOptionData();
        $this->addDummyStandardVariantAttributeOptionData();
        $this->addDummyUomAttributeOptionData();
        $this->addDummyVendorAttributeOptionData();
        $this->cron->execute();
        $this->assertAddSoftAttributeOption();
        $this->assertAddHardAttributeOption();
        $this->assertAddStardardVariantAttributeOption();
        $this->assertAddUomAttributeOption();
        $this->assertAddVendorAttributeOption();
    }

    /**
     * @magentoDbIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'store', 'default'),
        Config(LSR::CONVERT_ATTRIBUTE_TO_VISUAL_SWATCH, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::CONVERT_ATTRIBUTE_TO_VISUAL_SWATCH, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'store', 'default'),
    ]
    public function testHardAttributeOptionUpdate()
    {
        $this->executeUntilReady();

        $extendedVariant = $this->getFirstExtendedVariant();
        if ($extendedVariant) {
            $this->replExtendedVariantValueRepository->save(
                $extendedVariant->addData(['is_updated' => 1, 'LogicalOrder' => 1000])
            );

            $this->cron->execute();

            $this->eavConfig->clear();
            $eavAttribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_HARD_ATTRIBUTE)
            );

            $sortOrderChanged = false;
            foreach ($eavAttribute->getSource()->getAllOptions() as $index => $option) {
                if ($option['label'] == $extendedVariant->getValue() &&
                    $index == count($eavAttribute->getSource()->getAllOptions()) - 1) {
                    $sortOrderChanged = true;
                }
            }

            $this->assertTrue($sortOrderChanged);
        }
    }

    /**
     * @magentoDbIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'store', 'default'),
    ]
    public function testSoftAttributeRemoval()
    {
        $storeId       = $this->storeManager->getStore()->getWebsiteId();
        $filters       = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'Code', 'value' => AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE, 'condition_type' => 'eq']
        ];
        $criteria      = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        $replAttribute = current($this->replAttributeRepositoryInterface->getList($criteria)->getItems());

        if ($replAttribute) {
            $this->replAttributeRepositoryInterface->save(
                $replAttribute->addData(['is_updated' => 1, 'IsDeleted' => 1])
            );

            $this->cron->execute();

            $this->eavConfig->clear();
            $eavAttribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE)
            );

            $isVisibleOnFront = $eavAttribute->getData('is_visible_on_front');

            $this->assertEquals(0, $isVisibleOnFront);
        }
    }

    /**
     * @magentoDbIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'store',  'default'),
        Config(LSR::CONVERT_ATTRIBUTE_TO_VISUAL_SWATCH, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::CONVERT_ATTRIBUTE_TO_VISUAL_SWATCH, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'store', 'default'),
    ]
    public function testVisualSwatchAttribute()
    {
        $this->cron->execute();

        $this->eavConfig->clear();
        $eavAttribute   = $this->eavConfig->getAttribute(
            Product::ENTITY,
            AbstractIntegrationTest::SAMPLE_VISUAL_SWATCH_ATTRIBUTE_CODE
        );
        $additionalData = $eavAttribute->getData('additional_data');
        $isVisualSwatch = false;

        if (!empty($additionalData)) {
            $additionalData = $this->serializer->unserialize($additionalData);

            if (is_array($additionalData) &&
                isset($additionalData[Swatch::SWATCH_INPUT_TYPE_KEY]) &&
                $additionalData[Swatch::SWATCH_INPUT_TYPE_KEY] == Swatch::SWATCH_INPUT_TYPE_VISUAL
            ) {
                $isVisualSwatch = true;
            }
        }

        $this->assertTrue($isVisualSwatch);
    }

    /**
     * @magentoDbIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL."_1", 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE),
        Config(LSR::CONVERT_ATTRIBUTE_TO_VISUAL_SWATCH, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::ENABLED, 'store', 'default'),
    ]
    public function testLsrDown()
    {
        $this->executeUntilReady();
        $storeId = $this->storeManager->getStore()->getId();

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_ATTRIBUTE,
                LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
                LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT
            ],
            $storeId,
            false
        );
    }

    public function getFirstExtendedVariant()
    {
        $storeId  = $this->storeManager->getStore()->getWebsiteId();
        $filters  = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'Code', 'value' => AbstractIntegrationTest::SAMPLE_HARD_ATTRIBUTE, 'condition_type' => 'eq'],
            [
                'field'          => 'ItemId',
                'value'          => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                'condition_type' => 'eq'
            ]
        ];
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);

        return current($this->replExtendedVariantValueRepository->getList($criteria)->getItems());
    }

    public function addDummySoftAttributeOptionData()
    {
        $option = $this->replAttributeOptionValueInterfaceFactory->create();

        $option->addData(
            [
                'Code'      => AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE,
                'IsDeleted' => 0,
                'Sequence'  => 80000,
                'Value'     => self::SAMPLE_NEW_SOFT_ATTRIBUTE_OPTION_LABEL,
                'scope'     => ScopeInterface::SCOPE_WEBSITES,
                'scope_id'  => $this->storeManager->getStore()->getWebsiteId()
            ]
        );
        $this->replAttributeOptionValueRepositoryInterface->save($option);
    }

    public function addDummyHardAttributeOptionData()
    {
        $option = $this->replExtendedVariantValueInterfaceFactory->create();

        $option->addData(
            [
                'Code'                  => AbstractIntegrationTest::SAMPLE_HARD_ATTRIBUTE,
                'CodeDescription'       => AbstractIntegrationTest::SAMPLE_HARD_ATTRIBUTE,
                'DimensionLogicalOrder' => 1,
                'Dimensions'            => 2,
                'FrameworkCode'         => 'WOMEN',
                'IsDeleted'             => 0,
                'ItemId'                => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                'LogicalOrder'          => 50,
                'Value'                 => self::SAMPLE_NEW_HARD_ATTRIBUTE_OPTION_LABEL,
                'ValueDescription'      => self::SAMPLE_NEW_HARD_ATTRIBUTE_OPTION_LABEL,
                'scope'                 => ScopeInterface::SCOPE_WEBSITES,
                'scope_id'              => $this->storeManager->getStore()->getWebsiteId()
            ]
        );
        $this->replExtendedVariantValueRepository->save($option);
    }

    public function addDummyStandardVariantAttributeOptionData()
    {
        $option = $this->replItemVariantInterfaceFactory->create();

        $option->addData(
            [
                'Description' => 'Test Product',
                'Description2' => self::SAMPLE_NEW_STANDARD_VARIANT_OPTION_LABEL,
                'IsDeleted' => 0,
                'ItemId'  => 4444,
                'VariantId' => 000,
                'scope'     => ScopeInterface::SCOPE_WEBSITES,
                'scope_id'  => $this->storeManager->getStore()->getWebsiteId()
            ]
        );
        $this->replItemVariantRepository->save($option);
    }

    public function addDummyUomAttributeOptionData()
    {
        $option = $this->replUnitOfMeasureInterfaceFactory->create();

        $option->addData(
            [
                'Description'      => self::SAMPLE_NEW_UOM_ATTRIBUTE_OPTION_LABEL,
                'nav_id'           => self::SAMPLE_NEW_UOM_ATTRIBUTE_OPTION_LABEL,
                'IsDeleted'        => 0,
                'ShortDescription' => self::SAMPLE_NEW_UOM_ATTRIBUTE_OPTION_LABEL,
                'UnitDecimals'     => 0,
                'scope'            => ScopeInterface::SCOPE_WEBSITES,
                'scope_id'         => $this->storeManager->getStore()->getWebsiteId()
            ]
        );
        $this->replUnitOfMeasureRepository->save($option);
    }

    public function addDummyVendorAttributeOptionData()
    {
        $option = $this->replVendorInterfaceFactory->create();

        $option->addData(
            [
                'AllowCustomersToSelectPageSize' => '0',
                'Blocked'                        => '0',
                'DisplayOrder'                   => '1',
                'nav_id'                         => self::SAMPLE_NEW_VENDOR_ATTRIBUTE_OPTION_LABEL,
                'IsDeleted'                      => 0,
                'ManufacturerTemplateId'         => 1,
                'Name'                           => self::SAMPLE_NEW_VENDOR_ATTRIBUTE_OPTION_LABEL,
                'PageSize'                       => '4',
                'PictureId'                      => 0,
                'Published'                      => 1,
                'scope'                          => ScopeInterface::SCOPE_WEBSITES,
                'scope_id'                       => $this->storeManager->getStore()->getWebsiteId()
            ]
        );
        $this->replVendorRepository->save($option);
    }

    public function assertAddSoftAttributeOption()
    {
        $newOptionExists = $this->givenOptionExistsInAttribute(
            $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE),
            self::SAMPLE_NEW_SOFT_ATTRIBUTE_OPTION_LABEL
        );

        $this->assertTrue($newOptionExists);
    }

    public function givenOptionExistsInAttribute($attributeCode, $optionLabel)
    {
        $this->eavConfig->clear();
        $eavAttribute = $this->eavConfig->getAttribute(
            Product::ENTITY,
            $attributeCode
        );

        $newOptionExists = false;

        foreach ($eavAttribute->getSource()->getAllOptions() as $option) {
            if ($option['label'] == $optionLabel) {
                $newOptionExists = true;
                break;
            }
        }

        return $newOptionExists;
    }

    public function assertAddHardAttributeOption()
    {
        $newOptionExists = $this->givenOptionExistsInAttribute(
            $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_HARD_ATTRIBUTE),
            self::SAMPLE_NEW_HARD_ATTRIBUTE_OPTION_LABEL
        );

        $this->assertTrue($newOptionExists);
    }

    public function assertAddStardardVariantAttributeOption()
    {
        $newOptionExists = $this->givenOptionExistsInAttribute(
            $this->replicationHelper->formatAttributeCode(LSR::LS_STANDARD_VARIANT_ATTRIBUTE_CODE),
            self::SAMPLE_NEW_STANDARD_VARIANT_OPTION_LABEL
        );

        $this->assertTrue($newOptionExists);
    }

    public function assertAddUomAttributeOption()
    {
        $newOptionExists = $this->givenOptionExistsInAttribute(
            LSR::LS_UOM_ATTRIBUTE,
            self::SAMPLE_NEW_UOM_ATTRIBUTE_OPTION_LABEL
        );

        $this->assertTrue($newOptionExists);
    }

    public function assertAddVendorAttributeOption()
    {
        $newOptionExists = $this->givenOptionExistsInAttribute(
            LSR::LS_VENDOR_ATTRIBUTE,
            self::SAMPLE_NEW_VENDOR_ATTRIBUTE_OPTION_LABEL
        );

        $this->assertTrue($newOptionExists);
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
        $cronAttributeCheck                = $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_ATTRIBUTE,
            ScopeInterface::SCOPE_STORES,
            $scopeId
        );
        $cronAttributeVariantCheck         = $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
            ScopeInterface::SCOPE_STORES,
            $scopeId
        );
        $cronAttributeStandardVariantCheck = $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT,
            ScopeInterface::SCOPE_STORES,
            $scopeId
        );
        return $cronAttributeCheck && $cronAttributeVariantCheck && $cronAttributeStandardVariantCheck;
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

    public function assertAttributesCreated($attributes)
    {
        foreach ($attributes as $attribute) {
            $eavAttribute = $this->eavConfig->getAttribute(Product::ENTITY, $attribute);

            $this->assertNotNull($eavAttribute);
            $this->assertNotEmpty($eavAttribute->getSource()->getAllOptions());
        }
    }
}
