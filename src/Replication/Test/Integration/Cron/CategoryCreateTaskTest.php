<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\Data\ReplHierarchyNodeInterfaceFactory;
use \Ls\Replication\Api\ReplHierarchyNodeRepositoryInterface as ReplHierarchyNodeRepository;
use \Ls\Replication\Cron\CategoryCreateTask;
use \Ls\Replication\Cron\ReplEcommHierarchyNodeTask;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
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
            'job_url' => ReplEcommHierarchyNodeTask::class,
            'scope'   => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
]
class CategoryCreateTaskTest extends TestCase
{
    public const SAMPLE_MAIN_HIERARCHY_NODE_NAV_ID = 'TEST_MAIN_NODE';

    public const SAMPLE_SUB_HIERARCHY_NODE_NAV_ID = 'TEST_SUB_NODE';

    /** @var ObjectManagerInterface */
    public $objectManager;

    public $cron;

    public $lsr;

    public $storeManager;

    public $replicationHelper;

    /** @var CollectionFactory */
    public $collectionFactory;

    /** @var ReplHierarchyNodeRepository */
    public $replHierarchyNodeRepository;

    /** @var CategoryRepositoryInterface */
    public $categoryRepository;

    /**
     * @var ReplHierarchyNodeInterfaceFactory
     */
    public $replHierarchyNodeInterfaceFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager     = Bootstrap::getObjectManager();
        $this->cron              = $this->objectManager->create(CategoryCreateTask::class);
        $this->lsr               = $this->objectManager->create(\Ls\Core\Model\Lsr::class);
        $this->storeManager      = $this->objectManager->get(StoreManagerInterface::class);
        $this->replicationHelper = $this->objectManager->get(ReplicationHelper::class);
        $this->collectionFactory = $this->objectManager->get(CollectionFactory::class);
        $this->replHierarchyNodeRepository = $this->objectManager->get(ReplHierarchyNodeRepository::class);
        $this->categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
        $this->replHierarchyNodeInterfaceFactory = $this->objectManager->get(ReplHierarchyNodeInterfaceFactory::class);
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
        Config(LSR::SC_REPLICATION_HIERARCHY_CODE, AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID, 'store', 'default')
    ]
    public function testExecute()
    {
        $this->executeUntilReady();
        $storeId = $this->storeManager->getStore()->getId();

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_CATEGORY
            ],
            $storeId
        );

        $categories = $this->getCategory(null, $storeId);

        $this->assertTrue($categories->getSize() > 1);
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
        Config(LSR::SC_REPLICATION_HIERARCHY_CODE, AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID, 'store', 'default')
    ]
    public function testAddNewHierarchyNode()
    {
        $this->executeUntilReady();
        $this->addDummyHierarchyNodeData(self::SAMPLE_MAIN_HIERARCHY_NODE_NAV_ID);
        $this->addDummyHierarchyNodeData(
            self::SAMPLE_SUB_HIERARCHY_NODE_NAV_ID,
            self::SAMPLE_MAIN_HIERARCHY_NODE_NAV_ID
        );
        $this->cron->execute();
        $this->assertMainHierarchyNode(self::SAMPLE_MAIN_HIERARCHY_NODE_NAV_ID);
        $this->assertMainHierarchyNode(self::SAMPLE_SUB_HIERARCHY_NODE_NAV_ID);
    }

    public function assertMainHierarchyNode($navId)
    {
        $category = $this->getCategory($navId, true);
        $this->assertTrue($category !== false);
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
        Config(LSR::SC_REPLICATION_HIERARCHY_CODE, AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID, 'store', 'default')
    ]
    public function testHierarchyNodeRemoval()
    {
        $this->executeUntilReady();
        $storeId = $this->storeManager->getStore()->getId();
        $filters       = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            [
                'field' => 'nav_id',
                'value' => AbstractIntegrationTest::SAMPLE_HIERARCHY_NODE_NAV_ID,
                'condition_type' => 'eq'
            ]
        ];
        $criteria      = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        $replHierarchy = current($this->replHierarchyNodeRepository->getList($criteria)->getItems());
        $this->replHierarchyNodeRepository->save(
            $replHierarchy->addData(['IsDeleted' => 1, 'is_updated' => 1])
        );

        $this->cron->execute();
        $category = $this->getCategory(AbstractIntegrationTest::SAMPLE_HIERARCHY_NODE_NAV_ID, $storeId);
        $this->assertTrue($category !== false);
        $category = $this->categoryRepository->get($category->getId());
        $this->assertTrue($category->getData('is_active') === '0');
    }

    /**
     * @magentoDbIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE),
        Config(LSR::SC_REPLICATION_HIERARCHY_CODE, AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID, 'store', 'default')
    ]
    public function testLsrDown()
    {
        $this->executeUntilReady();
        $storeId = $this->storeManager->getStore()->getId();

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_CATEGORY
            ],
            $storeId,
            false
        );

        $categories = $this->getCategory(null, $storeId);

        $this->assertTrue($categories->getSize() === 0);
    }

    public function isReady($scopeId)
    {
        return $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_CATEGORY,
            ScopeInterface::SCOPE_STORES,
            $scopeId
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

    /**
     * Check if the category already exist or not
     *
     * @param $nav_id
     * @param bool $store
     * @return bool|DataObject
     * @throws LocalizedException
     */
    public function getCategory($nav_id, $store = false)
    {
        $collection = $this->collectionFactory->create();
        if ($store) {
            $collection->addPathsFilter('1/' . $this->cron->getRootCategoryId() . '/');
        }

        if ($nav_id) {
            $collection->addAttributeToFilter('nav_id', $nav_id);

            $collection->setPageSize(1);
            if ($collection->getSize()) {
                // @codingStandardsIgnoreStart
                return $collection->getFirstItem();
                // @codingStandardsIgnoreEnd
            } else {
                return false;
            }
        }

        return $collection;
    }

    public function addDummyHierarchyNodeData($navId, $parentNode = null)
    {
        $option = $this->replHierarchyNodeInterfaceFactory->create();

        $option->addData(
            [
                'ChildrenOrder' => 1,
                'Description' => 'Test Node',
                'HierarchyCode' => AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID,
                'nav_id' => $navId,
                'ImageId' => 'H-ACCESSORIES',
                'Indentation' => 0,
                'IsDeleted' => 0,
                'PresentationOrder' => 1,
                'ParentNode' => $parentNode,
                'scope'     => ScopeInterface::SCOPE_WEBSITES,
                'scope_id'  => $this->storeManager->getStore()->getId()
            ]
        );
        $this->replHierarchyNodeRepository->save($option);
    }
}
