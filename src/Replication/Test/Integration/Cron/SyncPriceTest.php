<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Cron\SyncPrice;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;

class SyncPriceTest extends AbstractTaskTest
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

        $this->updatePrice();
    }

    public function updatePrice()
    {
        $storeId           = $this->storeManager->getStore()->getId();
        $this->addDummyPriceData(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID
        );
        $this->modifySpecificItemPrice(AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID);
        $this->modifySpecificItemPrice(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            null,
            AbstractIntegrationTest::SAMPLE_UOM_2
        );

        $this->modifySpecificItemPrice(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID
        );

        $this->executeUntilReady(SyncPrice::class, [
            LSR::SC_SUCCESS_CRON_PRODUCT_PRICE
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_PRODUCT_PRICE,
            ],
            $storeId
        );
        $simpleProduct = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
            '',
            '',
            $storeId
        );

        $childProduct1 = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID
        );

        $childProduct2 = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID
        );

        $this->assertPrice($simpleProduct);
        $this->assertPrice($childProduct1);
        $this->assertPrice($childProduct2);
        $items = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '',
            AbstractIntegrationTest::SAMPLE_UOM_2,
            $storeId,
            true,
            true,
            true
        );

        foreach ($items as $item) {
            $this->assertPrice($item);
        }
    }

    public function modifySpecificItemPrice($itemId, $variantId = null, $uom = null)
    {
        $simpleItemPrice = $this->cron->getItemPrice($itemId, $variantId, $uom);

        $simpleItemPrice->addData(
            [
                'UnitPriceInclVat' => '10.0000',
                'is_updated' => 1
            ]
        );

        $this->replPriceRepository->save($simpleItemPrice);
    }
}
