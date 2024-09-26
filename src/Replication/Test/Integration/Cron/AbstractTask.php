<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\Data\ReplAttributeValueInterfaceFactory;
use \Ls\Replication\Api\Data\ReplHierarchyLeafInterfaceFactory;
use \Ls\Replication\Api\Data\ReplInvStatusInterfaceFactory;
use \Ls\Replication\Api\Data\ReplItemVariantInterfaceFactory;
use \Ls\Replication\Api\Data\ReplPriceInterfaceFactory;
use \Ls\Replication\Api\ReplAttributeValueRepositoryInterface;
use \Ls\Replication\Api\ReplHierarchyLeafRepositoryInterface;
use \Ls\Replication\Api\ReplInvStatusRepositoryInterface;
use \Ls\Replication\Api\ReplItemRepositoryInterface;
use \Ls\Replication\Api\ReplItemUnitOfMeasureRepositoryInterface;
use \Ls\Replication\Api\ReplItemVariantRegistrationRepositoryInterface;
use \Ls\Replication\Api\ReplItemVariantRepositoryInterface;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Api\ReplPriceRepositoryInterface as ReplPriceRepository;
use \Ls\Replication\Model\ReplItem;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\CatalogInventory\Model\StockRegistryStorage;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogAdminUi\Model\GetSourceItemsDataBySku;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AbstractTask extends TestCase
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

    public $cron;

    public $lsr;

    public $storeManager;

    public $replicationHelper;

    public $replItemVariantRegistrationRepository;

    public $replItemUomRepository;

    public $replHierarchyLeafRepository;

    public $resource;
    public $replItemRespository;

    public $productRepository;
    public $replItemVariantInterfaceFactory;
    public $replItemVariantRepository;

    public $objectManager;
    public $replHierarchyLeafInterfaceFactory;
    public $categoryRepository;
    public $replPriceRepository;
    public $replPriceInterfaceFactory;
    public $replItemInvRespository;

    public $replItemInvInterfaceFactory;
    public $stockItemRepository;
    public $sourceItems;
    public $stockRegistry;
    public $replAttributeValueRepository;
    public $replAttributeValueInterfaceFactory;

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
        $this->replItemVariantRegistrationRepository = $this->objectManager->get(ReplItemVariantRegistrationRepositoryInterface::class);
        $this->replItemUomRepository                 = $this->objectManager->get(ReplItemUnitOfMeasureRepositoryInterface::class);
        $this->replHierarchyLeafRepository           = $this->objectManager->get(ReplHierarchyLeafRepositoryInterface::class);
        $this->replItemRespository                   = $this->objectManager->get(ReplItemRepositoryInterface::class);
        $this->productRepository                     = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->resource                              = $this->objectManager->create(ResourceConnection::class);
        $this->replItemVariantInterfaceFactory       = $this->objectManager->get(ReplItemVariantInterfaceFactory::class);
        $this->replItemVariantRepository             = $this->objectManager->get(ReplItemVariantRepositoryInterface::class);
        $this->replHierarchyLeafInterfaceFactory     = $this->objectManager->get(ReplHierarchyLeafInterfaceFactory::class);
        $this->replPriceInterfaceFactory             = $this->objectManager->get(ReplPriceInterfaceFactory::class);
        $this->replItemInvInterfaceFactory           = $this->objectManager->get(ReplInvStatusInterfaceFactory::class);
        $this->replAttributeValueInterfaceFactory    = $this->objectManager->get(ReplAttributeValueInterfaceFactory::class);
        $this->replItemInvRespository                = $this->objectManager->get(ReplInvStatusRepositoryInterface::class);
        $this->categoryRepository                    = $this->objectManager->get(CategoryRepositoryInterface::class);
        $this->replPriceRepository                   = $this->objectManager->get(ReplPriceRepository::class);
        $this->stockItemRepository                   = $this->objectManager->get(StockItemRepository::class);
        $this->sourceItems                           = $this->objectManager->get(GetSourceItemsDataBySku::class);
        $this->stockRegistry                         = $this->objectManager->get(StockRegistryStorage::class);
        $this->replAttributeValueRepository          = $this->objectManager->get(ReplAttributeValueRepositoryInterface::class);
    }

    public function executeUntilReady($cronClass, $successStatus)
    {
        for ($i = 0; $i < 5; $i++) {
            $cron = $this->objectManager->create($cronClass);
            $cron->execute();

            if ($this->isReady($successStatus, $this->storeManager->getStore()->getId())) {
                break;
            }
        }
    }

    public function isReady($successStatuses, $scopeId)
    {
        $status = true;

        foreach ($successStatuses as $successStatus) {
            $status =
                $status && $this->lsr->getConfigValueFromDb($successStatus, ScopeInterface::SCOPE_STORES, $scopeId) &&
                $successStatus !== LSR::SC_SUCCESS_CRON_PRODUCT;
        }
        return $status;
    }

    public function updateAllRelevantItemRecords($value = 0, $itemId = '')
    {
        if (is_array($itemId)) {
            foreach ($itemId as $id) {
                $this->updateGivenItemFlatTablesData($value, $id);
            }
        } else {
            $this->updateGivenItemFlatTablesData($value, $itemId);
        }
    }

    public function updateGivenItemFlatTablesData($value, $itemId)
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

    public function assertPrice($product)
    {
        $storeId       = $this->storeManager->getStore()->getId();
        $itemId        = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $variantId     = $product->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);
        $uomCode       = $product->getData("uom");
        $item          = $this->getReplItem($itemId, $storeId);
        $unitOfMeasure = null;

        if (!empty($uomCode)) {
            if ($uomCode != $item->getBaseUnitOfMeasure()) {
                $unitOfMeasure = $uomCode;
            }
        }
        $itemPrice = $this->cron->getItemPrice($itemId, $variantId, $unitOfMeasure);
        if (isset($itemPrice)) {
            $this->assertTrue($product->getPrice() == $itemPrice->getUnitPriceInclVat());
        } else {
            $itemPrice = $this->cron->getItemPrice($itemId);
            if (!empty($itemPrice)) {
                $this->assertTrue($product->getPrice() == $itemPrice->getUnitPriceInclVat());
            } else {
                $this->assertTrue($product->getPrice() == $itemPrice->getUnitPrice());
            }
        }
    }

    public function assertInventory($product, $isStandardVariant = 0)
    {
        $scopeId     = $this->storeManager->getStore()->getId();
        $itemId      = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $variantId   = $product->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);
        $webStoreId  = $this->cron->webStoreId;
        $itemStock   = $this->replicationHelper->getInventoryStatus($itemId, $webStoreId, $scopeId, $variantId);

        if ($isStandardVariant && empty($itemStock)) {
            $itemStock   = $this->replicationHelper->getInventoryStatus($itemId, $webStoreId, $scopeId);
        }

        if (!empty($itemStock)) {
            $stockItem   = $this->stockItemRepository->get($product->getId());
            $sourceItems = $this->sourceItems->execute($product->getSku());

            if ($product->getTypeId() == Type::TYPE_SIMPLE) {
                $this->assertTrue($itemStock->getQuantity() == $stockItem->getQty());
            }

            if ($itemStock->getQuantity() > 0) {
                $this->assertTrue((bool)$stockItem->getIsInStock());
            } else {
                $this->assertFalse((bool)$stockItem->getIsInStock());
            }
            foreach ($sourceItems as $sourceItem) {
                if ($product->getTypeId() == Type::TYPE_SIMPLE) {
                    $this->assertTrue($itemStock->getQuantity() == $sourceItem['quantity']);
                }
                if ($itemStock->getQuantity() > 0) {
                    $this->assertTrue((bool)$sourceItem['status']);
                } else {
                    $this->assertFalse((bool)$sourceItem['status']);
                }
            }
        }
    }

    public function getReplItem($itemId, $storeId)
    {
        $filters = [
            ['field' => 'nav_id', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq']
        ];

        $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, 1);
        /** @var ReplItem $item */
        $item = current($this->replItemRespository->getList($searchCriteria)->getItems());

        return $item;
    }

    public function addDummyDataStandardVariant()
    {
        $this->addDummyStandardVariantAttributeOptionData(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
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

        $this->addDummyPriceData(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID
        );

        $this->addDummyInventoryData(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID
        );

        $this->addDummyInventoryData(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            '001',
        );

        $this->addDummyInventoryData(
            AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID,
            '002',
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

    public function addDummyPriceData($itemId, $variantId, $uomCode = null)
    {
        $price = $this->replPriceInterfaceFactory->create();
        $price->addData(
            [
                'CurrencyCode' => AbstractIntegrationTest::SAMPLE_CURRENCY_CODE,
                'EndingDate' => '1900-01-01T00:00:00',
                'IsDeleted' => 0,
                'ItemId' => $itemId,
                'MinimumQuantity' => '0.0000',
                'ModifyDate' => '2024-08-01T00:00:00',
                'PriceInclVat' => 0,
                'Priority' => 0,
                'QtyPerUnitOfMeasure' => '0.0000',
                'StartingDate' => '1900-01-01T00:00:00',
                'StoreId' => AbstractIntegrationTest::CS_STORE,
                'UnitOfMeasure' => $uomCode,
                'UnitPrice' => '12.0000',
                'UnitPriceInclVat' => '14.0000',
                'VariantId' => $variantId,
                'scope' => ScopeInterface::SCOPE_WEBSITES,
                'scope_id' => $this->storeManager->getWebsite()->getId()
            ]
        );
        $this->replPriceRepository->save($price);
    }

    public function addDummyInventoryData($itemId, $variantId = null)
    {
        $invStatus = $this->replItemInvInterfaceFactory->create();

        $invStatus->addData(
            [
                'IsDeleted' => 0,
                'ItemId' => $itemId,
                'Quantity' => 100.0000,
                'StoreId' => AbstractIntegrationTest::CS_STORE,
                'VariantId' => $variantId,
                'scope' => ScopeInterface::SCOPE_WEBSITES,
                'scope_id' => $this->storeManager->getWebsite()->getId()
            ]
        );
        $this->replItemInvRespository->save($invStatus);
    }

    public function assertSoftAttributes($product, $isChild = 0)
    {
        $itemId = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $variantId = $product->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);

        if ($isChild && empty($variantId)) {
            return;
        }
        $scopeId = $this->storeManager->getWebsite()->getId();
        $softAttributes = $this->getSoftAttributes($itemId, $scopeId, $variantId);

        foreach ($softAttributes as $softAttribute) {
            $itemId        = $softAttribute->getLinkField1();
            $variantId     = $softAttribute->getLinkField2();
            $formattedCode = $this->replicationHelper->formatAttributeCode($softAttribute->getCode());
            $attribute     = $this->replicationHelper->eavConfig->getAttribute('catalog_product', $formattedCode);

            if (!$attribute->getId()) {
                continue;
            }

            if ($attribute->getFrontendInput() == 'multiselect') {
                $value = $this->replicationHelper->getAllValuesForGivenMultiSelectAttribute(
                    $itemId,
                    $variantId,
                    $softAttribute->getCode(),
                    $formattedCode,
                    $scopeId
                );
            } elseif ($attribute->getFrontendInput() == 'boolean') {
                if (strtolower($softAttribute->getValue()) == 'yes') {
                    $value = 1;
                } else {
                    $value = 0;
                }
            } else {
                $value = $softAttribute->getValue();
            }
            if (isset($formattedCode)) {
                $this->assertTrue($product->getData($formattedCode) == $value);
            }
        }
    }

    public function getSoftAttributes($itemId, $storeId, $variantId = null, $attributeCode = null)
    {
        $filters = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'LinkField1', 'value' => $itemId, 'condition_type' => 'eq'],
            [
                'field' => 'LinkField2',
                'value' => is_null($variantId) ? true : $variantId,
                'condition_type' => is_null($variantId)  ? 'null' : 'eq'
            ]
        ];

        if (isset($attributeCode)) {
            $filters[] = ['field' => 'Code', 'value' => $attributeCode, 'condition_type' => 'eq'];
        }

        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);

        return $this->replAttributeValueRepository->getList($criteria)->getItems();
    }
}
