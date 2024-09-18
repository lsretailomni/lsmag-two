<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\Data\ReplHierarchyLeafInterfaceFactory;
use \Ls\Replication\Api\Data\ReplItemVariantInterfaceFactory;
use \Ls\Replication\Api\Data\ReplPriceInterfaceFactory;
use \Ls\Replication\Api\ReplHierarchyLeafRepositoryInterface;
use \Ls\Replication\Api\ReplItemRepositoryInterface;
use \Ls\Replication\Api\ReplItemUnitOfMeasureRepositoryInterface;
use \Ls\Replication\Api\ReplItemVariantRegistrationRepositoryInterface;
use \Ls\Replication\Api\ReplItemVariantRepositoryInterface;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Api\ReplPriceRepositoryInterface as ReplPriceRepository;
use \Ls\Replication\Model\ReplItem;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
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
    public $stockItemRepository;
    public $sourceItems;

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
        $this->categoryRepository                    = $this->objectManager->get(CategoryRepositoryInterface::class);
        $this->replPriceRepository                   = $this->objectManager->get(ReplPriceRepository::class);
        $this->stockItemRepository                   = $this->objectManager->get(StockItemRepository::class);
        $this->sourceItems                           = $this->objectManager->get(GetSourceItemsDataBySku::class);
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
            } catch (\Exception $e) {
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

    public function assertInventory($product)
    {
        $scopeId     = $this->storeManager->getStore()->getId();
        $itemId      = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $variantId   = $product->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);
        $webStoreId  = $this->cron->webStoreId;
        $itemStock   = $this->replicationHelper->getInventoryStatus($itemId, $webStoreId, $scopeId, $variantId);
        $stockItem   = $this->stockItemRepository->get($product->getId());
        $sourceItems = $this->sourceItems->execute($product->getSku());
        $this->assertTrue($itemStock->getQuantity() == $stockItem->getQty());
        if ($itemStock->getQuantity() > 0) {
            $this->assertTrue((bool)$stockItem->getIsInStock());
        }
        foreach ($sourceItems as $sourceItem) {
            $this->assertTrue($itemStock->getQuantity() == $sourceItem['quantity']);

            if ($itemStock->getQuantity() > 0) {
                $this->assertTrue((bool)$sourceItem['status']);
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
}
