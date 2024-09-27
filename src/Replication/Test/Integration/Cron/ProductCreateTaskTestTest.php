<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ProductCreateTaskTestTest extends AbstractTaskTest
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
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID,
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

        $configurableProduct = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '',
            '',
            $storeId
        );

        $configurableProductWithUomOnly = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
            '',
            '',
            $storeId
        );

        $configurableProductWithVariantOnly  = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
            '',
            '',
            $storeId
        );
        $configurableProduct2WithVariantOnly = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID,
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

        $configurableProductWithStandardVariant = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            '',
            '',
            $storeId
        );

        $this->assertSimpleProducts($simpleProduct);
        $this->assertConfigurableProducts($configurableProduct);
        $this->assertConfigurableProducts($configurableProductWithUomOnly);
        $this->assertConfigurableProducts($configurableProductWithVariantOnly);
        $this->assertConfigurableProducts($configurableProduct2WithVariantOnly);
        $this->assertStandardConfigurableProducts($configurableProductWithStandardVariant);
        $this->stockRegistry->_resetState();
        $this->updateProducts();
        $this->removeProducts();
    }

    public function removeProducts()
    {
        $storeId = $this->storeManager->getStore()->getId();

        $replItemConf = $this->getReplItem(AbstractIntegrationTest::SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID, $storeId);
        $replVariant  = $this->getVariant(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID,
            $storeId,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID
        );

        $replUomOnly = $this->getUom(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
            $storeId,
            AbstractIntegrationTest::SAMPLE_UOM_2
        );
        $this->deleteReplItemUomData([$replUomOnly]);
        $this->deleteReplItemData(
            [$replItemConf]
        );
        $this->deleteVariantItemData([$replVariant]);

        $this->executeUntilReady(ProductCreateTask::class, [
            LSR::SC_SUCCESS_CRON_PRODUCT
        ]);

        $configurableProduct = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID,
            '',
            $storeId
        );

        $variantProduct = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
            '',
            $storeId
        );

        $uomProductVariant = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
            '',
            AbstractIntegrationTest::SAMPLE_UOM_2,
            $storeId
        );

        $this->assertTrue((bool)($configurableProduct->getData('status') == Status::STATUS_DISABLED));
        $this->assertTrue((bool)($variantProduct->getData('status') == Status::STATUS_DISABLED));
        $this->assertTrue((bool)($uomProductVariant->getData('status') == Status::STATUS_DISABLED));
    }

    public function updateProducts()
    {
        $storeId                     = $this->storeManager->getStore()->getId();
        $replItemConf                = $this->getReplItem(AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID, $storeId);
        $replItemConfWithUomOnly     = $this->getReplItem(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
            $storeId
        );
        $replItemConfWithVariantOnly = $this->getReplItem(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
            $storeId
        );
        $replItemSimple              = $this->getReplItem(AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID, $storeId);
        $replVariant                 = $this->getVariant(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            $storeId,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID
        );
        $replVariantWithVariantOnly  = $this->getVariant(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
            $storeId,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID
        );

        $replUomOnly = $this->getUom(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
            $storeId,
            AbstractIntegrationTest::SAMPLE_UOM
        );
        $this->updateReplItemUomData([$replUomOnly]);

        $this->updateReplItemData(
            [$replItemConf, $replItemSimple, $replItemConfWithVariantOnly, $replItemConfWithUomOnly]
        );
        $this->updateVariantItemData([$replVariant]);
        $this->updateVariantItemData([$replVariantWithVariantOnly]);
        $this->executeUntilReady(ProductCreateTask::class, [
            LSR::SC_SUCCESS_CRON_PRODUCT
        ]);

        $configurableProduct = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '',
            '',
            $storeId
        );

        $configurableProductWithUomOnly = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
            '',
            '',
            $storeId
        );

        $configurableProductWithVariantOnly = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
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
        $this->assertConfigurableProducts($configurableProductWithUomOnly);
        $this->assertConfigurableProducts($configurableProductWithVariantOnly);
    }

    public function updateReplItemUomData($replUoms)
    {
        foreach ($replUoms as $replUom) {
            $replUom->addData(
                [
                    'EComSelection' => 1,
                    'is_updated' => 1
                ]
            );

            $this->replItemUomRepository->save($replUom);
        }
    }

    public function updateReplItemData($replItemSimple)
    {
        foreach ($replItemSimple as $item) {
            $item->addData(
                [
                    'BlockedOnECom' => 1,
                    'is_updated' => 1
                ]
            );

            $this->replItemRespository->save($item);
        }
    }

    public function updateVariantItemData($replItemSimple)
    {
        foreach ($replItemSimple as $item) {
            $item->addData(
                [
                    'BlockedOnECom' => 1,
                    'is_updated' => 1
                ]
            );

            $this->replItemVariantRegistrationRepository->save($item);
        }
    }

    public function deleteReplItemUomData($replUoms)
    {
        foreach ($replUoms as $replUom) {
            $replUom->addData(
                [
                    'IsDeleted' => 1,
                    'is_updated' => 1
                ]
            );

            $this->replItemUomRepository->save($replUom);
        }
    }

    public function deleteReplItemData($replItemSimple)
    {
        foreach ($replItemSimple as $item) {
            $item->addData(
                [
                    'IsDeleted' => 1,
                    'is_updated' => 1
                ]
            );

            $this->replItemRespository->save($item);
        }
    }

    public function deleteVariantItemData($replItemSimple)
    {
        foreach ($replItemSimple as $item) {
            $item->addData(
                [
                    'IsDeleted' => 1,
                    'is_updated' => 1
                ]
            );

            $this->replItemVariantRegistrationRepository->save($item);
        }
    }

    public function assertSimpleProducts($simpleProduct)
    {
        $this->assertTrue($simpleProduct->getTypeId() == Type::TYPE_SIMPLE);
        $this->assertAssignedCategories($simpleProduct);
        $this->assertCustomAttributes($simpleProduct);
        $this->assertPrice($simpleProduct);
        $this->assertInventory($simpleProduct);
    }

    public function assertConfigurableProducts($configurableProduct)
    {
        $this->assertTrue($configurableProduct->getTypeId() == Configurable::TYPE_CODE);
        $this->assertVariants($configurableProduct);
        $this->assertAssignedCategories($configurableProduct);
        $this->assertCustomAttributes($configurableProduct);
    }

    public function assertStandardConfigurableProducts($configurableProduct)
    {
        $this->assertTrue($configurableProduct->getTypeId() == Configurable::TYPE_CODE);
        $this->assertCustomAttributes($configurableProduct);
        $this->assertStandardVariants($configurableProduct);
    }

    public function assertCustomAttributes($product)
    {
        $scopeId = $this->storeManager->getWebsite()->getId();
        $itemId  = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);

        $item         = $this->getReplItem($itemId, $scopeId);
        $itemBarcodes = $this->cron->_getBarcode($item->getNavId());
        $this->assertTrue($product->getData('name') == $item->getDescription());
        $this->assertTrue($product->getData('meta_title') == $item->getDescription());
        $this->assertTrue($product->getData('description') == $item->getDetails());
        $this->assertTrue(
            (bool)($product->getData('status') ==
            $item->getBlockedOnECom() ? Status::STATUS_DISABLED : Status::STATUS_ENABLED)
        );
        $this->assertTrue($product->getData('uom') == $item->getBaseUnitOfMeasure());
        $this->assertEqualsCanonicalizing($product->getWebsiteIds(), [$this->storeManager->getStore()->getWebsiteId()]);
        $this->assertTrue($product->getData(LSR::LS_TARIFF_NO_ATTRIBUTE_CODE) == $item->getTariffNo());
        $this->assertTrue($product->getData(LSR::LS_ITEM_PRODUCT_GROUP) == $item->getProductGroupId());
        $this->assertTrue($product->getData(LSR::LS_ITEM_CATEGORY) == $item->getItemCategoryCode());
        $this->assertTrue($product->getData(LSR::LS_ITEM_SPECIAL_GROUP) == $item->getSpecialGroups());
        $this->assertTrue($product->getData('country_of_manufacture') == $item->getCountryOfOrigin());

        if (!empty($item->getTaxItemGroupId())) {
            $taxClass = $this->replicationHelper->getTaxClassGivenName(
                $item->getTaxItemGroupId()
            );

            if (!empty($taxClass)) {
                $this->assertTrue($product->getData('tax_class_id') == $taxClass->getClassId());
            }
        }
        if (isset($itemBarcodes[$item->getNavId()])) {
            $this->assertTrue($product->getData('barcode') == $itemBarcodes[$item->getNavId()]);
        }

        $this->assertSoftAttributes($product);

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $children = $product->getTypeInstance()->getUsedProducts($product);

            foreach ($children as $child) {
                $this->assertSoftAttributes($child, 1);
                if (isset($itemBarcodes[$child->getSku()])) {
                    $this->assertTrue(
                        $this->productRepository->get($child->getSku())->getData('barcode') ==
                        $itemBarcodes[$child->getSku()]
                    );
                }
            }
        }
    }

    public function assertStandardVariants($configurableProduct)
    {
        $storeId              = $this->storeManager->getStore()->getId();
        $itemId               = $configurableProduct->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $replItem             = $this->getReplItem($itemId, $storeId);
        $standardVariants     = $this->cron->getStandardProductVariants($itemId);
        $associatedProductIds = $configurableProduct->getTypeInstance()->getUsedProductIds($configurableProduct);

        $this->assertEquals(count($standardVariants), count($associatedProductIds));
        foreach ($standardVariants as $variant) {
            $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                $itemId,
                $variant->getVariantId(),
                '',
                $storeId
            );
            $name        = $this->cron->getNameForStandardVariant($variant, $replItem);
            $this->assertTrue($productData->getData('name') == $name);
            $this->assertTrue($productData->getData('name') == $name);
            $this->assertTrue($productData->getData('meta_title') == $name);
            $this->assertTrue($productData->getData('description') == $replItem->getDetails());
            $this->assertPrice($productData);
            $this->assertInventory($productData, 1);
        }
    }

    public function assertVariants($configurableProduct)
    {
        $storeId  = $this->storeManager->getStore()->getId();
        $itemId   = $configurableProduct->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $replItem = $this->getReplItem($itemId, $storeId);

        $uoms                     = $this->replicationHelper->getUomCodes($itemId, $storeId);
        $replUoms                 = $this->getUom($itemId, $storeId);
        $itemUomCount             = !empty($uoms[$itemId]) ? count($uoms[$itemId]) : 1;
        $variants                 = $this->getVariant($itemId, $storeId);
        $variantRegistrationCount = !empty($variants) ? count($variants) : 1;
        $associatedProductIds     = $configurableProduct->getTypeInstance()->getUsedProductIds($configurableProduct);

        $this->assertEquals($itemUomCount * $variantRegistrationCount, count($associatedProductIds));

        if (!empty($replUoms) && count($uoms[$itemId]) > 1 && !empty($variants)) {
            foreach ($replUoms as $uom) {
                foreach ($variants as $variant) {
                    $productData = null;
                    try {
                        $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                            $itemId,
                            $variant->getVariantId(),
                            $uom->getCode(),
                            $storeId
                        );
                    } catch (Exception $exception) {
                    }
                    $this->assertNotNull($productData);
                    $uomDescription = $this->replicationHelper->getUomDescriptionGivenCodeAndScopeId(
                        $uom->getCode(),
                        $storeId
                    );
                    $name           = $this->cron->getNameForVariant($variant, $replItem);
                    $name           = $this->cron->getNameForUom($name, $uomDescription);

                    $this->assertTrue($productData->getData('name') == $name);
                    $this->assertTrue($productData->getData('meta_title') == $name);
                    $this->assertTrue($productData->getData('description') == $replItem->getDetails());
                    $this->assertTrue(
                        (bool)($productData->getData('status') ==
                        $variant->getBlockedOnECom() ? Status::STATUS_DISABLED : Status::STATUS_ENABLED)
                    );

                    $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_QTY) == $uom->getQtyPrUOM());
                    $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_HEIGHT) == $uom->getHeight());
                    $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_LENGTH) == $uom->getLength());
                    $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_WIDTH) == $uom->getWidth());
                    $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_CUBAGE) == $uom->getCubage());
                    $this->assertPrice($productData);
                    $this->assertInventory($productData);
                }
            }
        } elseif (!empty($replUoms) && empty($variants)) {
            foreach ($replUoms as $uom) {
                $productData = null;
                try {
                    $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                        $itemId,
                        '',
                        $uom->getCode(),
                        $storeId
                    );
                } catch (Exception $exception) {
                }
                $this->assertNotNull($productData);
                $uomDescription = $this->replicationHelper->getUomDescriptionGivenCodeAndScopeId(
                    $uom->getCode(),
                    $storeId
                );
                $name           = $this->cron->getNameForUom($replItem->getDescription(), $uomDescription);

                $this->assertTrue($productData->getData('name') == $name);
                $this->assertTrue($productData->getData('meta_title') == $name);
                $this->assertTrue($productData->getData('description') == $replItem->getDetails());
                $this->assertTrue(
                    (bool)($productData->getData('status') ==
                    $uom->getEComSelection() ? Status::STATUS_DISABLED : Status::STATUS_ENABLED)
                );

                $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_QTY) == $uom->getQtyPrUOM());
                $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_HEIGHT) == $uom->getHeight());
                $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_LENGTH) == $uom->getLength());
                $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_WIDTH) == $uom->getWidth());
                $this->assertTrue($productData->getData(LSR::LS_UOM_ATTRIBUTE_CUBAGE) == $uom->getCubage());
                $this->assertPrice($productData);
                $this->assertInventory($productData);
            }
        } else {
            foreach ($variants as $variant) {
                $productData = null;
                try {
                    $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                        $itemId,
                        $variant->getVariantId(),
                        '',
                        $storeId
                    );
                } catch (Exception $exception) {
                }
                $this->assertNotNull($productData);
                $name = $this->cron->getNameForVariant($variant, $replItem);

                $this->assertTrue($productData->getData('name') == $name);
                $this->assertTrue($productData->getData('meta_title') == $name);
                $this->assertTrue($productData->getData('description') == $replItem->getDetails());
                $this->assertTrue(
                    (bool)($productData->getData('status') ==
                    $variant->getBlockedOnECom() ? Status::STATUS_DISABLED : Status::STATUS_ENABLED)
                );
                $this->assertPrice($productData);
                $this->assertInventory($productData);
            }
        }
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

    public function getVariant($itemId, $storeId, $variantId = null)
    {
        if ($variantId == null) {
            return $this->cron->getProductVariants($itemId);
        }
        $filters = [
            ['field' => 'VariantId', 'value' => $variantId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq']
        ];

        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);

        return current($this->replItemVariantRegistrationRepository->getList($criteria)->getItems());
    }

    public function getUom($itemId, $storeId, $uom = null)
    {
        $filters = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq']
        ];

        if ($uom) {
            $filters[] = ['field' => 'Code', 'value' => $uom, 'condition_type' => 'eq'];
        }

        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        $items    = $this->replItemUomRepository->getList($criteria)->getItems();

        return $uom ? current($items) : $items;
    }
}
