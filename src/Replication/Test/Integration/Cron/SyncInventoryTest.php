<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Cron\SyncInventory as SyncInventoryAlias;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;

class SyncInventoryTest extends AbstractTaskTest
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
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID
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
        $this->updateInventory();
        $this->updateInventory(1);
    }

    public function updateInventory($outOfStock = 0)
    {
        $this->stockRegistry->_resetState();
        $storeId           = $this->storeManager->getStore()->getId();
        $this->modifySpecificItemInv(AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID, null, $outOfStock);
        $this->modifySpecificItemInv(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
            $outOfStock
        );
        $this->modifySpecificItemInv(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
            $outOfStock
        );
        $this->modifySpecificItemInv(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
            null,
            $outOfStock
        );

        $this->modifySpecificItemInv(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
            $outOfStock
        );
        $this->executeUntilReady(SyncInventoryAlias::class, [
            LSR::SC_SUCCESS_CRON_PRODUCT_INVENTORY
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_PRODUCT_INVENTORY,
            ],
            $storeId
        );
        $simpleProduct = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
            '',
            '',
            $storeId
        );

        $this->assertInventory($simpleProduct);

        $this->assertMultipleItemsInventory(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
            $storeId
        );
        $this->assertMultipleItemsInventory(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
            $storeId
        );
        $this->assertMultipleItemsInventory(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
            null,
            $storeId
        );

        $this->assertMultipleItemsInventory(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
            $storeId,
            1
        );
    }

    public function assertMultipleItemsInventory($itemId, $variantId, $storeId, $isStandardVariant = 0)
    {
        $items = $this->replicationHelper->getProductDataByIdentificationAttributes(
            $itemId,
            $variantId,
            null,
            $storeId,
            true,
            true,
            true
        );

        foreach ($items as $item) {
            $this->assertInventory($item, $isStandardVariant);
        }
    }

    public function modifySpecificItemInv($itemId, $variantId = null, $outOfStock = 0)
    {
        $itemStock = $this->replicationHelper->getInventoryStatus(
            $itemId,
            AbstractIntegrationTest::CS_STORE,
            $this->storeManager->getWebsite()->getId(),
            $variantId
        );
        $itemStock->addData(
            [
                'is_updated' => 1,
                'Quantity' => $outOfStock ? 0.0000 : 388.0000
            ]
        );

        $this->replItemInvRespository->save($itemStock);
    }
}
