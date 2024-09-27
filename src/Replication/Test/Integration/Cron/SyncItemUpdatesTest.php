<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Cron\SyncItemUpdates;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Store\Model\ScopeInterface;

class SyncItemUpdatesTest extends AbstractTaskTest
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

    public function actualExecute()
    {
        $storeId           = $this->storeManager->getStore()->getId();

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

        $this->updateNewCategories($storeId);
        $this->removeCategories($storeId);
    }

    public function updateNewCategories($storeId)
    {
        $this->categoryRepository->_resetState();
        $this->productRepository->_resetState();
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
        $this->addDummyHierarchyLeafData(
            $configurableProduct->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE),
            AbstractIntegrationTest::SAMPLE_HIERARCHY_LEAF
        );
        $this->addDummyHierarchyLeafData(
            $simpleProduct->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE),
            AbstractIntegrationTest::SAMPLE_HIERARCHY_LEAF
        );

        $this->executeUntilReady(SyncItemUpdates::class, [
            LSR::SC_SUCCESS_CRON_ITEM_UPDATES
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_ITEM_UPDATES,
            ],
            $storeId
        );

        $categoryIds = $this->replicationHelper->findCategoryIdFromFactory(
            AbstractIntegrationTest::SAMPLE_HIERARCHY_LEAF,
            $this->storeManager->getStore()
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

        $this->assertTrue(!array_diff($categoryIds, $configurableProduct->getCategoryIds()));
        $this->assertTrue(!array_diff($categoryIds, $simpleProduct->getCategoryIds()));
    }

    public function removeCategories($storeId)
    {
        $this->categoryRepository->_resetState();
        $this->productRepository->_resetState();
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
        $this->addDummyHierarchyLeafData(
            $configurableProduct->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE),
            AbstractIntegrationTest::SAMPLE_HIERARCHY_LEAF,
            1
        );
        $this->addDummyHierarchyLeafData(
            $simpleProduct->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE),
            AbstractIntegrationTest::SAMPLE_HIERARCHY_LEAF,
            1
        );

        $this->executeUntilReady(SyncItemUpdates::class, [
            LSR::SC_SUCCESS_CRON_ITEM_UPDATES
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_ITEM_UPDATES,
            ],
            $storeId
        );

        $categoryIds = $this->replicationHelper->findCategoryIdFromFactory(
            AbstractIntegrationTest::SAMPLE_HIERARCHY_LEAF,
            $this->storeManager->getStore()
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

        $this->assertFalse(!array_diff($categoryIds, $configurableProduct->getCategoryIds()));
        $this->assertFalse(!array_diff($categoryIds, $simpleProduct->getCategoryIds()));
    }

    public function addDummyHierarchyLeafData($itemId, $nodeId, $isDeleted = 0)
    {
        if ($isDeleted) {
            $filters = [
                ['field' => 'nav_id', 'value' => $itemId, 'condition_type' => 'eq'],
                ['field' => 'NodeId', 'value' => $nodeId, 'condition_type' => 'eq'],
                [
                    'field' => 'scope_id',
                    'value' => $this->storeManager->getWebsite()->getId(),
                    'condition_type' => 'eq'
                ],
            ];

            $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, 1);
            $option = current($this->replHierarchyLeafRepository->getList(
                $searchCriteria
            )->getItems());
            $option->addData(
                [
                'is_updated' => 1,
                'IsDeleted' => 1
                ]
            );
        } else {
            $option = $this->replHierarchyLeafInterfaceFactory->create();
            $option->addData(
                [
                    'DealPrice' => '0.0000',
                    'Description' => 'Leather backpack',
                    'HierarchyCode' => AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID,
                    'nav_id' => $itemId,
                    'ImageId' => $itemId,
                    'IsActive' => 0,
                    'IsDeleted' => 0,
                    'IsMemberClub' => 0,
                    'NodeId' => $nodeId,
                    'Prepayment' => '0.0000',
                    'SortOrder' => 30000,
                    'Type' => 'Item',
                    'VendorSourcing' => 0,
                    'is_updated' => 0,
                    'scope' => ScopeInterface::SCOPE_WEBSITES,
                    'scope_id' => $this->storeManager->getWebsite()->getId(),
                ]
            );
        }

        $this->replHierarchyLeafRepository->save($option);
    }
}
