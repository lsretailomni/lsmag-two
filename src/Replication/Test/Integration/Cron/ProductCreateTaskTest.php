<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use Exception;
use Ls\Core\Model\LSR;
use Ls\Replication\Api\Data\ReplHierarchyNodeInterfaceFactory;
use Ls\Replication\Api\ReplHierarchyLeafRepositoryInterface;
use Ls\Replication\Api\ReplHierarchyNodeRepositoryInterface as ReplHierarchyNodeRepository;
use Ls\Replication\Api\ReplItemRepositoryInterface;
use Ls\Replication\Api\ReplItemUnitOfMeasureRepositoryInterface;
use Ls\Replication\Api\ReplItemVariantRegistrationRepositoryInterface;
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
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Replication\Model\ReplItem;
use Ls\Replication\Test\Fixture\FlatDataReplication;
use Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ResourceConnection;
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
            'job_url' => ReplEcommAttributeTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommAttributeOptionValueTask::class,
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
            'job_url' => ReplEcommItemUnitOfMeasuresTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommVendorTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommHierarchyNodeTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommHierarchyLeafTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemsTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommBarcodesTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemVariantRegistrationsTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommPricesTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommImageLinksTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommInventoryStatusTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    )
]
class ProductCreateTaskTest extends TestCase
{
    public $lsTables = [
        ['table' => 'ls_replication_repl_item', 'id' => 'nav_id'],
        ['table' => 'ls_replication_repl_item_variant_registration', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_item_variant', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_extended_variant_value', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_price', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_barcode', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_inv_status', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_hierarchy_leaf', 'id' => 'nav_id'],
        ['table' => 'ls_replication_repl_attribute_value', 'id' => 'LinkField1'],
        ['table' => 'ls_replication_repl_image_link', 'id' => 'KeyValue'],
        ['table' => 'ls_replication_repl_item_unit_of_measure', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_loy_vendor_item_mapping', 'id' => 'NavProductId'],
        ['table' => 'ls_replication_repl_item_modifier', 'id' => 'nav_id'],
        ['table' => 'ls_replication_repl_item_recipe', 'id' => 'RecipeNo'],
        ['table' => 'ls_replication_repl_hierarchy_hosp_deal', 'id' => 'DealNo'],
        ['table' => 'ls_replication_repl_hierarchy_hosp_deal_line', 'id' => 'DealNo'],
    ];
    public $objectManager;

    public $cron;

    public $lsr;

    public $storeManager;

    public $replicationHelper;

    public $collectionFactory;

    public $replHierarchyNodeRepository;

    public $categoryRepository;

    public $replHierarchyNodeInterfaceFactory;

    public $replItemVariantRegistrationRepository;

    public $replItemUomRepository;

    public $replHierarchyLeafRepository;

    public $attributeCron;

    public $categoryCron;

    public $resource;


    public $connection;
    public $replItemRespository;

    public $productRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager                         = Bootstrap::getObjectManager();
        $this->cron                                  = $this->objectManager->create(ProductCreateTask::class);
        $this->lsr                                   = $this->objectManager->create(\Ls\Core\Model\Lsr::class);
        $this->storeManager                          = $this->objectManager->get(StoreManagerInterface::class);
        $this->replicationHelper                     = $this->objectManager->get(ReplicationHelper::class);
        $this->collectionFactory                     = $this->objectManager->get(CollectionFactory::class);
        $this->replHierarchyNodeRepository           = $this->objectManager->get(ReplHierarchyNodeRepository::class);
        $this->categoryRepository                    = $this->objectManager->get(CategoryRepositoryInterface::class);
        $this->replHierarchyNodeInterfaceFactory     = $this->objectManager->get(ReplHierarchyNodeInterfaceFactory::class);
        $this->replItemVariantRegistrationRepository = $this->objectManager->get(ReplItemVariantRegistrationRepositoryInterface::class);
        $this->replItemUomRepository                 = $this->objectManager->get(ReplItemUnitOfMeasureRepositoryInterface::class);
        $this->replHierarchyLeafRepository           = $this->objectManager->get(ReplHierarchyLeafRepositoryInterface::class);
        $this->replItemRespository                   = $this->objectManager->get(ReplItemRepositoryInterface::class);
        $this->productRepository                     = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->attributeCron                         = $this->objectManager->create(AttributesCreateTask::class);
        $this->categoryCron                          = $this->objectManager->create(CategoryCreateTask::class);
        $this->resource                              = $this->objectManager->create(ResourceConnection::class);
        $this->connection                            = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
    }

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
        Config(LSR::SC_REPLICATION_HIERARCHY_CODE, AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID, 'store', 'default'),
        Config(LSR::SC_REPLICATION_PRODUCT_BATCHSIZE, 5, 'store', 'default')
    ]
    public function testExecute()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $this->executePreReqCrons();
        $this->updateAllRelevantItemRecords(
            1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID
        );

        $this->updateAllRelevantItemRecords(
            1,
            AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID
        );

        $this->executeUntilReady($this->cron, [
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

        $this->assertSimpleProducts($simpleProduct);
        $this->assertConfigurableProducts($configurableProduct);
    }

    public function assertSimpleProducts($simpleProduct)
    {
        $this->assertTrue($simpleProduct->getTypeId() == Type::TYPE_SIMPLE);
        $this->assertAssignedCategories($simpleProduct);
        $this->assertCustomAttributes($simpleProduct);
    }

    public function assertConfigurableProducts($configurableProduct)
    {
        $this->assertTrue($configurableProduct->getTypeId() == Configurable::TYPE_CODE);
        $this->assertVariantsCount($configurableProduct);
        $this->assertAssignedCategories($configurableProduct);
        $this->assertCustomAttributes($configurableProduct);
    }

    public function assertCustomAttributes($product)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $itemId = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);

        $filters = [
            ['field' => 'nav_id', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq']
        ];

        $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, 1);
        /** @var ReplItem $item */
        $item = current($this->replItemRespository->getList($searchCriteria)->getItems());
        $itemBarcodes = $this->cron->_getBarcode($item->getNavId());
        $this->assertTrue($product->getData('uom') == $item->getBaseUnitOfMeasure());
        $this->assertTrue($product->getData(LSR::LS_TARIFF_NO_ATTRIBUTE_CODE) == $item->getTariffNo());
        $this->assertTrue($product->getData(LSR::LS_ITEM_PRODUCT_GROUP) == $item->getProductGroupId());
        $this->assertTrue($product->getData(LSR::LS_ITEM_CATEGORY) == $item->getItemCategoryCode());
        $this->assertTrue($product->getData(LSR::LS_ITEM_SPECIAL_GROUP) == $item->getSpecialGroups());

        if (isset($itemBarcodes[$item->getNavId()])) {
            $this->assertTrue($product->getData('barcode') == $itemBarcodes[$item->getNavId()]);
        }

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $children = $product->getTypeInstance()->getUsedProducts($product);

            foreach ($children as $child) {
                if (isset($itemBarcodes[$child->getSku()])) {
                    $this->assertTrue(
                        $this->productRepository->get($child->getSku())->getData('barcode') ==
                        $itemBarcodes[$child->getSku()]
                    );
                }
            }
        }
    }

    public function assertVariantsCount($configurableProduct)
    {
        $itemId    = $configurableProduct->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $storeId   = $this->storeManager->getStore()->getId();
        $filters1  = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            [
                'field'          => 'ItemId',
                'value'          => $itemId,
                'condition_type' => 'eq'
            ]
        ];
        $criteria1 = $this->replicationHelper->buildCriteriaForDirect($filters1, -1);

        $filters2  = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            [
                'field'          => 'ItemId',
                'value'          => $itemId,
                'condition_type' => 'eq'
            ]
        ];
        $criteria2 = $this->replicationHelper->buildCriteriaForDirect($filters2, -1);

        $itemUomCount = count($this->replItemUomRepository->getList($criteria2)->getItems());

        $variantRegistrationCount = count($this->replItemVariantRegistrationRepository->getList($criteria1)->getItems());
        $associatedProductIds     = $configurableProduct->getTypeInstance()->getUsedProductIds($configurableProduct);

        $this->assertEquals($itemUomCount * $variantRegistrationCount, count($associatedProductIds));
    }

    public function assertAssignedCategories($product)
    {
        $productCategoryIds   = $product->getCategoryIds();
        $store                = $this->storeManager->getStore();
        $hierarchyCode        = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE, $store->getId());
        $filters              = [
            ['field' => 'NodeId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $store->getWebsiteId(), 'condition_type' => 'eq'],
            [
                'field' => 'nav_id',
                'value' => $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE), 'condition_type' => 'eq'
            ]
        ];
        $criteria             = $this->replicationHelper->buildCriteriaForDirect($filters);
        $hierarchyLeafs       = $this->replHierarchyLeafRepository->getList($criteria);
        $resultantCategoryIds = [];
        foreach ($hierarchyLeafs->getItems() as $hierarchyLeaf) {
            $categoryIds = $this->replicationHelper->findCategoryIdFromFactory($hierarchyLeaf->getNodeId(), $store);
            if (!empty($categoryIds)) {
                // @codingStandardsIgnoreLine
                $resultantCategoryIds = array_unique(array_merge($resultantCategoryIds, $categoryIds));
            }
        }

        if (!empty($resultantCategoryIds) && !empty($productCategoryIds)) {
            $this->assertEqualsCanonicalizing($resultantCategoryIds, $productCategoryIds);
            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                $children = $product->getTypeInstance()->getUsedProducts($product);

                foreach ($children as $child) {
                    $this->assertEqualsCanonicalizing($resultantCategoryIds, $child->getCategoryIds());
                }
            }
        }
    }

    public function executePreReqCrons()
    {
        $this->executeUntilReady($this->attributeCron, [
            LSR::SC_SUCCESS_CRON_ATTRIBUTE,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT
        ]);

        $this->executeUntilReady($this->categoryCron, [
            LSR::SC_SUCCESS_CRON_CATEGORY
        ]);
        $this->updateAllRelevantItemRecords();
    }

    public function updateAllRelevantItemRecords($value = 0, $itemId = '')
    {
        // Update all dependent ls tables to processed = 0
        foreach ($this->lsTables as $lsTable) {
            $lsTableName = $this->resource->getTableName($lsTable['table']);
            $columnName  = $lsTable['id'];

            if ($value == 0) {
                $bind  = ['processed' => 1];
                $where = [];
            } else {
                $bind  = [
                    'is_updated' => 1
                ];
                $where = [];

                if ($columnName == 'KeyValue') {
                    $where["$columnName like ?"] = "%$itemId";
                } else {
                    $where["$columnName = ?"] = $itemId;
                }
            }
            try {
                $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
                $connection->update(
                    $lsTableName,
                    $bind,
                    $where
                );
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }

    public function isReady($successStatuses, $scopeId)
    {
        $status = true;

        foreach ($successStatuses as $successStatus) {
            $status =
                $status && $this->lsr->getConfigValueFromDb($successStatus, ScopeInterface::SCOPE_STORES, $scopeId);
        }
        return $status;
    }

    public function executeUntilReady($cron, $successStatus)
    {
        for ($i = 0; $i < 3; $i++) {
            $cron->execute();

            if ($this->isReady($successStatus, $this->storeManager->getStore()->getId())) {
                break;
            }
        }
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
