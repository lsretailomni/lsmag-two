<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Cron\SyncAttributesValue;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Catalog\Model\Product\Type;
use Magento\Store\Model\ScopeInterface;

class SyncAttributesValueTest extends AbstractTaskTest
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

        $this->updateAttributesValue();
    }

    public function updateAttributesValue()
    {
        $storeId           = $this->storeManager->getStore()->getId();
        $this->modifySpecificAttributeValue(
            AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE,
            AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
            null,
            'Metal'
        );

        $this->modifySpecificAttributeValue(
            AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            null,
            'Metal'
        );

        $this->modifySpecificAttributeValue(
            AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
            'Polyester'
        );

        $this->executeUntilReady(SyncAttributesValue::class, [
            LSR::SC_SUCCESS_CRON_ATTRIBUTES_VALUE
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_ATTRIBUTES_VALUE,
            ],
            $storeId
        );
        $simpleProduct = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
            '',
            '',
            $storeId
        );

        $this->assertSoftAttributes(
            $simpleProduct
        );

        $this->assertMultipleItemsAttributeValue(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            null,
            $storeId
        );
    }

    public function assertMultipleItemsAttributeValue($itemId, $variantId, $storeId)
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
            $this->assertSoftAttributes(
                $item,
                $item->getTypeId() == Type::TYPE_SIMPLE ? 1 : 0
            );
        }
    }

    public function modifySpecificAttributeValue($attributeCode, $itemId, $variantId = null, $value = null)
    {
        $scopeId = $this->storeManager->getWebsite()->getId();
        $softAttribute = current($this->getSoftAttributes($itemId, $scopeId, $variantId, $attributeCode));

        if ($softAttribute) {
            $softAttribute->addData(
                [
                    'is_updated' => 1,
                    'Value' => $value
                ]
            );
        } else {
            $softAttribute = $this->replAttributeValueInterfaceFactory->create();
            $softAttribute->addData(
                [
                    'Code' => $attributeCode,
                    'IsDeleted' => 0,
                    'LinkField1' => $itemId,
                    'LinkField2' => $variantId,
                    'LinkType' => 0,
                    'NumbericValue' => 0.0000,
                    'Sequence' => 1000,
                    'Value' => $value,
                    'scope' => ScopeInterface::SCOPE_WEBSITES,
                    'scope_id' => $this->storeManager->getWebsite()->getId()
                ]
            );
        }

        $this->replAttributeValueRepository->save($softAttribute);
    }
}
