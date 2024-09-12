<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\AttributesCreateTask;
use \Ls\Replication\Cron\CategoryCreateTask;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Cron\ReplEcommAttributeOptionValueTask;
use \Ls\Replication\Cron\ReplEcommAttributeTask;
use \Ls\Replication\Cron\ReplEcommAttributeValueTask;
use \Ls\Replication\Cron\ReplEcommBarcodesTask;
use \Ls\Replication\Cron\ReplEcommExtendedVariantsTask;
use \Ls\Replication\Cron\ReplEcommHierarchyLeafTask;
use \Ls\Replication\Cron\ReplEcommHierarchyNodeTask;
use \Ls\Replication\Cron\ReplEcommImageLinksTask;
use \Ls\Replication\Cron\ReplEcommInventoryStatusTask;
use \Ls\Replication\Cron\ReplEcommItemsTask;
use \Ls\Replication\Cron\ReplEcommItemUnitOfMeasuresTask;
use \Ls\Replication\Cron\ReplEcommItemVariantRegistrationsTask;
use \Ls\Replication\Cron\ReplEcommItemVariantsTask;
use \Ls\Replication\Cron\ReplEcommPricesTask;
use \Ls\Replication\Cron\ReplEcommUnitOfMeasuresTask;
use \Ls\Replication\Cron\ReplEcommVendorTask;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;

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
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommAttributeOptionValueTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommAttributeValueTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommExtendedVariantsTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemVariantsTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommUnitOfMeasuresTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemUnitOfMeasuresTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommVendorTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommHierarchyNodeTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommHierarchyLeafTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemsTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommBarcodesTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommItemVariantRegistrationsTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommPricesTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommImageLinksTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommInventoryStatusTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    )
]
class ProductCreateTaskTest extends AbstractTask
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
        $storeId           = $this->storeManager->getStore()->getId();
        $this->cron->store = $this->storeManager->getStore();
        $this->cron->webStoreId = AbstractIntegrationTest::CS_STORE;
        $this->addDummyDataStandardVariant();
        $this->executePreReqCrons();

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

        $this->updateProducts();
        $this->removeProducts();
    }

    public function addDummyDataStandardVariant()
    {
        $this->addDummyStandardVariantAttributeOptionData(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            '000',
            'Small'
        );
        $this->addDummyStandardVariantAttributeOptionData(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            '001',
            'Medium'
        );
        $this->addDummyStandardVariantAttributeOptionData(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            '002',
            'Large'
        );
    }

    public function addDummyStandardVariantAttributeOptionData($itemId, $variantId, $desc)
    {
        $option = $this->replItemVariantInterfaceFactory->create();
        $option->addData(
            [
                'Description' => $desc,
                'Description2' => $desc,
                'IsDeleted' => 0,
                'ItemId' => $itemId,
                'VariantId' => $variantId,
                'scope' => ScopeInterface::SCOPE_WEBSITES,
                'scope_id' => $this->storeManager->getWebsite()->getId()
            ]
        );
        $this->replItemVariantRepository->save($option);
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
        $storeId = $this->storeManager->getStore()->getId();
        $itemId  = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);

        $item         = $this->getReplItem($itemId, $storeId);
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

    public function executePreReqCrons()
    {
        $this->executeUntilReady(AttributesCreateTask::class, [
            LSR::SC_SUCCESS_CRON_ATTRIBUTE,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT
        ]);

        $this->executeUntilReady(CategoryCreateTask::class, [
            LSR::SC_SUCCESS_CRON_CATEGORY
        ]);
        $this->updateAllRelevantItemRecords();
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
