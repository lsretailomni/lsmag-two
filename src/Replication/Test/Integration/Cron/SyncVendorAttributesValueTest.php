<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Cron\SyncVendorAttributesValue;
use \Ls\Replication\Model\ReplItem;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;

class SyncVendorAttributesValueTest extends AbstractTaskTest
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

        $this->executeUntilReady(SyncVendorAttributesValue::class, [
            LSR::SC_SUCCESS_CRON_VENDOR_ATTRIBUTE
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_VENDOR_ATTRIBUTE,
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

        $this->assertVendorAttribute($configurableProduct);
        $this->assertVendorAttribute($simpleProduct);

        $this->updateVendorAttributesValue();
    }

    public function updateVendorAttributesValue()
    {
        $scopeId = $this->storeManager->getWebsite()->getId();
        $storeId = $this->storeManager->getStore()->getId();
        $this->modifySpecificVentorItem(
            AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
            $scopeId,
        );
        $this->modifySpecificVentorItem(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            $scopeId,
        );

        $this->executeUntilReady(SyncVendorAttributesValue::class, [
            LSR::SC_SUCCESS_CRON_VENDOR_ATTRIBUTE
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_VENDOR_ATTRIBUTE,
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

        $this->assertVendorAttribute($configurableProduct);
        $this->assertVendorAttribute($simpleProduct);
    }

    public function modifySpecificVentorItem($itemId, $scopeId)
    {
        $vendorItem = $this->getVendorItem($itemId, $scopeId);
        $vendorItem->addData(
            [
                'NavManufacturerId' => '44000',
                'is_updated' => 1
            ]
        );
        $this->replVendorItemRepository->save($vendorItem);
    }

    public function assertVendorAttribute($product)
    {
        $scopeId    = $this->storeManager->getWebsite()->getId();
        $itemId     = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $vendorItem = $this->getVendorItem($itemId, $scopeId);
        $vendor     = $this->getVendor($itemId, $scopeId);
        $value      = $this->replicationHelper->_getOptionIDByCode(
            LSR::LS_VENDOR_ATTRIBUTE,
            $vendor->getName()
        );
        if (!empty($vendorItem) && !empty($value)) {
            $this->assertTrue($product->getData(LSR::LS_VENDOR_ATTRIBUTE) == $value);
            $this->assertTrue(
                $product->getData(LSR::LS_ITEM_VENDOR_ATTRIBUTE) == $vendorItem->getNavManufacturerItemId()
            );
        }
    }

    public function getVendorItem($itemId, $scopeId)
    {
        $filters = [
            ['field' => 'NavProductId', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq']
        ];

        $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, 1);
        return current($this->replVendorItemRepository->getList($searchCriteria)->getItems());
    }

    public function getVendor($itemId, $scopeId)
    {
        $vendorItem = $this->getVendorItem($itemId, $scopeId);

        $filters = [
            ['field' => 'nav_id', 'value' => $vendorItem->getNavManufacturerId(), 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq']
        ];

        $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, 1);
        /** @var ReplItem $item */
        return current($this->replVendorRepository->getList($searchCriteria)->getItems());
    }
}
