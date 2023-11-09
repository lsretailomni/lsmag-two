<?php

namespace Ls\Replication\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ItemType;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Model\InventoryCatalog\GetParentSkusOfChildrenSkus;
use \Ls\Replication\Api\Data\ReplItemUnitOfMeasureInterface;
use \Ls\Replication\Api\ReplAttributeValueRepositoryInterface;
use \Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use \Ls\Replication\Api\ReplHierarchyLeafRepositoryInterface as ReplHierarchyLeafRepository;
use \Ls\Replication\Api\ReplImageLinkRepositoryInterface;
use \Ls\Replication\Api\ReplInvStatusRepositoryInterface as ReplInvStatusRepository;
use \Ls\Replication\Api\ReplItemRepositoryInterface as ReplItemRepository;
use \Ls\Replication\Api\ReplItemUnitOfMeasureRepositoryInterface as ReplItemUnitOfMeasure;
use \Ls\Replication\Api\ReplStoreTenderTypeRepositoryInterface;
use \Ls\Replication\Api\ReplTaxSetupRepositoryInterface;
use \Ls\Replication\Api\ReplUnitOfMeasureRepositoryInterface;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplAttributeValue;
use \Ls\Replication\Model\ReplAttributeValueSearchResults;
use \Ls\Replication\Model\ReplExtendedVariantValue;
use \Ls\Replication\Model\ReplImageLinkSearchResults;
use \Ls\Replication\Model\ReplInvStatus;
use Ls\Replication\Model\ReplItem;
use \Ls\Replication\Model\ResourceModel\ReplAttributeValue\CollectionFactory as ReplAttributeValueCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplExtendedVariantValue\CollectionFactory as ReplExtendedVariantValueCollectionFactory;
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category as ResourceModelCategory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\StockStatusRepository;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\ConfigurableProduct\Model\Inventory\ParentItemProcessor;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProTypeModel;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Model\AttributeManagement;
use Magento\Eav\Model\AttributeSetManagement;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\GroupFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Framework\Api\AbstractExtensibleObject;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Inventory\Model\SourceItem\Command\SourceItemsSave;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryCatalog\Model\GetProductIdsBySkus;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterfaceFactory;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website\Interceptor;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Model\ClassModel;
use Magento\Tax\Model\ClassModelFactory;
use Symfony\Component\Filesystem\Filesystem as FileSystemDirectory;

/**
 * Useful helper functions for replication
 *
 */
class ReplicationHelper extends AbstractHelper
{
    public const VARIANT_ID_TABLE_ALIAS = 'variantId_table';
    public const ITEM_ID_TABLE_ALIAS = 'itemId_table';

    public const COLUMNS_MAPPING = [
        'catalog_product_entity_varchar' => [
            'entity_id' => 'row_id'
        ]
    ];

    /**
     * @var array
     */
    public $defaultMimeTypes = [
        'image/jpg',
        'image/jpeg',
        'image/gif',
        'image/png',
    ];

    /**
     * @var array
     */
    public $allowedUrlTypes = [
        'category',
        'product'
    ];

    public $connection;

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var Filesystem */
    public $filesystem;

    /** @var SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var FilterBuilder */
    public $filterBuilder;

    /** @var FilterGroupBuilder */
    public $filterGroupBuilder;

    /** @var ReplImageLinkRepositoryInterface */
    public $replImageLinkRepositoryInterface;

    /** @var Config */
    public $eavConfig;

    /** @var WriterInterface */
    public $configWriter;

    /** @var Set */
    public $attributeSet;

    /** @var TypeListInterface */
    public $cacheTypeList;

    /** @var LSR */
    public $lsr;

    /** @var ResourceConnection */
    public $resource;

    /** @var SortOrder */
    public $sortOrder;

    /** @var DateTime */
    public $dateTime;

    /** @var TimezoneInterface */
    public $timezone;

    /** @var Logger */
    public $_logger;

    /**
     * @var ReplItemRepository
     */
    public $itemRepository;

    /**
     * @var FileSystemDirectory
     */
    public $fileSystemDirectory;

    /** @var CollectionFactory */
    public $categoryCollectionFactory;

    /** @var ReplHierarchyLeafRepository */
    public $replHierarchyLeafRepository;

    /** @var CategoryLinkManagementInterface */
    public $categoryLinkManagement;

    /**  @var ReplAttributeValueCollectionFactory */
    public $replAttributeValueCollectionFactory;

    /**
     * @var ReplExtendedVariantValueCollectionFactory
     */
    public $replExtendedVariantValueCollectionFactory;

    /**
     * @var TypeFactory
     */
    public $eavTypeFactory;

    /**
     * @var SetFactory
     */
    public $attributeSetFactory;

    /**
     * @var AttributeSetManagement
     */
    public $attributeSetManagement;

    /**
     * @var AttributeManagement
     */
    public $attributeManagement;

    /**
     * @var GroupFactory
     */
    public $attributeSetGroupFactory;

    /**
     * @var AttributeGroupRepositoryInterface
     */
    public $attributeGroupRepository;

    /**
     * @var AttributeSetRepositoryInterface
     */
    public $attributeSetRepository;

    /**
     * @var ReplAttributeValueRepositoryInterface
     */
    public $replAttributeValueRepositoryInterface;

    /** @var ConfigurableProTypeModel */
    public $configurableProTypeModel;

    /** @var ReplExtendedVariantValueRepository */
    public $extendedVariantValueRepository;

    /** @var ReplItemUnitOfMeasure */
    public $replItemUomRepository;

    /** @var ReplTaxSetupRepositoryInterface */
    public $replTaxSetupRepository;

    /** @var ReplStoreTenderTypeRepositoryInterface */
    public $replStoreTenderTypeRepository;

    /**
     * @var TaxClassRepositoryInterface
     */
    public $taxClassRepository;

    /**
     * @var ClassModelFactory
     */
    public $classModelFactory;

    /** @var ReplInvStatusRepository */
    public $replInvStatusRepository;

    /**
     * @var SourceItemsSave
     */
    public $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory
     */
    public $sourceItemFactory;

    /**
     * @var DefaultSourceProviderInterfaceFactory
     */
    public $defaultSourceProviderFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public $productCollectionFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    public $categoryRepository;
    /**
     * @var ResourceModelCategory
     */
    public $categoryResourceModel;

    /**
     * @var RuleCollectionFactory
     */
    public $ruleCollectionFactory;

    /**
     * @var ReplUnitOfMeasureRepositoryInterface
     */
    public $replUnitOfMeasureRepository;

    /**
     * @var ParentItemProcessor
     */
    public $parentItemProcessor;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @var GetParentSkusOfChildrenSkus
     */
    public $getParentSkusOfChildrenSkus;

    /**
     * @var StockStatusRepository
     */
    public $stockStatusRepository;

    /**
     * @var GetProductIdsBySkus
     */
    public $getProductIdsBySkus;

    /**
     * @var AttributeFactory
     */
    public $eavAttributeFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    public $productResourceModel;

    /**
     * @var ProductMetadataInterface
     */
    public $productMetadata;

    /**
     * @var GetStockSourceLinksInterface
     */
    public $getStockSourceLinks;

    /**
     * @var SourceItemRepositoryInterface
     */
    public $sourceItemRepository;

    /**
     * @var SourceItemsDeleteInterface
     */
    public $sourceItemDeleteRepository;

    /**
     * @var GetAssignedStockIdForWebsite
     */
    public $getAssignedStockIdForWebsite;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    public $criteriaInterfaceFactory;

    /**
     * @var StockItemRepositoryInterface
     */
    public $stockItemRepository;

    /**
     * @var StockConfigurationInterface
     */
    public $stockConfiguration;

    /**
     * @var IsSingleSourceModeInterface
     */
    public $isSingleSourceMode;

    /**
     * @param Context $context
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $Filesystem
     * @param Config $eavConfig
     * @param WriterInterface $configWriter
     * @param Set $attributeSet
     * @param TypeListInterface $cacheTypeList
     * @param LSR $LSR
     * @param ResourceConnection $resource
     * @param SortOrder $sortOrder
     * @param DateTime $date
     * @param TimezoneInterface $timezone
     * @param Logger $_logger
     * @param ReplItemRepository $itemRepository
     * @param FileSystemDirectory $fileSystemDirectory
     * @param CollectionFactory $categoryCollectionFactory
     * @param ReplHierarchyLeafRepository $replHierarchyLeafRepository
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param ReplAttributeValueCollectionFactory $replAttributeValueCollectionFactory
     * @param ReplExtendedVariantValueCollectionFactory $replExtendedVariantValueCollectionFactory
     * @param TypeFactory $eavTypeFactory
     * @param SetFactory $attributeSetFactory
     * @param AttributeSetManagement $attributeSetManagement
     * @param AttributeManagement $attributeManagement
     * @param GroupFactory $attributeSetGroupFactory
     * @param AttributeGroupRepositoryInterface $attributeGroupRepository
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param ReplAttributeValueRepositoryInterface $replAttributeValueRepositoryInterface
     * @param ConfigurableProTypeModel $configurableProTypeModel
     * @param ReplExtendedVariantValueRepository $extendedVariantValueRepository
     * @param ReplItemUnitOfMeasure $replItemUomRepository
     * @param ReplTaxSetupRepositoryInterface $replTaxSetupRepository
     * @param ReplStoreTenderTypeRepositoryInterface $replStoreTenderTypeRepository
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param ClassModelFactory $classModelFactory
     * @param ReplInvStatusRepository $replInvStatusRepository
     * @param SourceItemsSave $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param DefaultSourceProviderInterfaceFactory $defaultSourceProviderFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ResourceModelCategory $categoryResourceModel
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param ReplUnitOfMeasureRepositoryInterface $replUnitOfMeasureRepository
     * @param ParentItemProcessor $parentItemProcessor
     * @param ProductRepositoryInterface $productRepository
     * @param GetParentSkusOfChildrenSkus $getParentSkusOfChildrenSkus
     * @param StockStatusRepository $stockStatusRepository
     * @param GetProductIdsBySkus $getProductIdsBySkus
     * @param AttributeFactory $eavAttributeFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResourceModel
     * @param ProductMetadataInterface $productMetadata
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SourceItemsDeleteInterface $sourceItemsDelete
     * @param GetAssignedStockIdForWebsite $getAssignedStockIdForWebsite
     * @param StockItemCriteriaInterfaceFactory $criteriaInterfaceFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockConfigurationInterface $stockConfiguration
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        Context $context,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface,
        StoreManagerInterface $storeManager,
        Filesystem $Filesystem,
        Config $eavConfig,
        WriterInterface $configWriter,
        Set $attributeSet,
        TypeListInterface $cacheTypeList,
        LSR $LSR,
        ResourceConnection $resource,
        SortOrder $sortOrder,
        DateTime $date,
        TimezoneInterface $timezone,
        Logger $_logger,
        ReplItemRepository $itemRepository,
        FileSystemDirectory $fileSystemDirectory,
        CollectionFactory $categoryCollectionFactory,
        ReplHierarchyLeafRepository $replHierarchyLeafRepository,
        CategoryLinkManagementInterface $categoryLinkManagement,
        ReplAttributeValueCollectionFactory $replAttributeValueCollectionFactory,
        ReplExtendedVariantValueCollectionFactory $replExtendedVariantValueCollectionFactory,
        TypeFactory $eavTypeFactory,
        SetFactory $attributeSetFactory,
        AttributeSetManagement $attributeSetManagement,
        AttributeManagement $attributeManagement,
        GroupFactory $attributeSetGroupFactory,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        AttributeSetRepositoryInterface $attributeSetRepository,
        ReplAttributeValueRepositoryInterface $replAttributeValueRepositoryInterface,
        ConfigurableProTypeModel $configurableProTypeModel,
        ReplExtendedVariantValueRepository $extendedVariantValueRepository,
        ReplItemUnitOfMeasure $replItemUomRepository,
        ReplTaxSetupRepositoryInterface $replTaxSetupRepository,
        ReplStoreTenderTypeRepositoryInterface $replStoreTenderTypeRepository,
        TaxClassRepositoryInterface $taxClassRepository,
        ClassModelFactory $classModelFactory,
        ReplInvStatusRepository $replInvStatusRepository,
        SourceItemsSave $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        DefaultSourceProviderInterfaceFactory $defaultSourceProviderFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        ResourceModelCategory $categoryResourceModel,
        RuleCollectionFactory $ruleCollectionFactory,
        ReplUnitOfMeasureRepositoryInterface $replUnitOfMeasureRepository,
        ParentItemProcessor $parentItemProcessor,
        ProductRepositoryInterface $productRepository,
        GetParentSkusOfChildrenSkus $getParentSkusOfChildrenSkus,
        StockStatusRepository $stockStatusRepository,
        GetProductIdsBySkus $getProductIdsBySkus,
        AttributeFactory $eavAttributeFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResourceModel,
        ProductMetadataInterface $productMetadata,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemsDeleteInterface $sourceItemsDelete,
        GetAssignedStockIdForWebsite $getAssignedStockIdForWebsite,
        StockItemCriteriaInterfaceFactory $criteriaInterfaceFactory,
        StockItemRepositoryInterface $stockItemRepository,
        StockConfigurationInterface $stockConfiguration,
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->searchCriteriaBuilder                     = $searchCriteriaBuilder;
        $this->filterBuilder                             = $filterBuilder;
        $this->filterGroupBuilder                        = $filterGroupBuilder;
        $this->storeManager                              = $storeManager;
        $this->filesystem                                = $Filesystem;
        $this->replImageLinkRepositoryInterface          = $replImageLinkRepositoryInterface;
        $this->eavConfig                                 = $eavConfig;
        $this->configWriter                              = $configWriter;
        $this->attributeSet                              = $attributeSet;
        $this->cacheTypeList                             = $cacheTypeList;
        $this->lsr                                       = $LSR;
        $this->resource                                  = $resource;
        $this->sortOrder                                 = $sortOrder;
        $this->dateTime                                  = $date;
        $this->timezone                                  = $timezone;
        $this->_logger                                   = $_logger;
        $this->itemRepository                            = $itemRepository;
        $this->fileSystemDirectory                       = $fileSystemDirectory;
        $this->categoryCollectionFactory                 = $categoryCollectionFactory;
        $this->replHierarchyLeafRepository               = $replHierarchyLeafRepository;
        $this->categoryLinkManagement                    = $categoryLinkManagement;
        $this->replAttributeValueCollectionFactory       = $replAttributeValueCollectionFactory;
        $this->replExtendedVariantValueCollectionFactory = $replExtendedVariantValueCollectionFactory;
        $this->eavTypeFactory                            = $eavTypeFactory;
        $this->attributeSetFactory                       = $attributeSetFactory;
        $this->attributeSetManagement                    = $attributeSetManagement;
        $this->attributeManagement                       = $attributeManagement;
        $this->attributeSetGroupFactory                  = $attributeSetGroupFactory;
        $this->attributeGroupRepository                  = $attributeGroupRepository;
        $this->attributeSetRepository                    = $attributeSetRepository;
        $this->replAttributeValueRepositoryInterface     = $replAttributeValueRepositoryInterface;
        $this->configurableProTypeModel                  = $configurableProTypeModel;
        $this->extendedVariantValueRepository            = $extendedVariantValueRepository;
        $this->replItemUomRepository                     = $replItemUomRepository;
        $this->replTaxSetupRepository                    = $replTaxSetupRepository;
        $this->replStoreTenderTypeRepository             = $replStoreTenderTypeRepository;
        $this->taxClassRepository                        = $taxClassRepository;
        $this->classModelFactory                         = $classModelFactory;
        $this->replInvStatusRepository                   = $replInvStatusRepository;
        $this->sourceItemsSave                           = $sourceItemsSave;
        $this->sourceItemFactory                         = $sourceItemFactory;
        $this->defaultSourceProviderFactory              = $defaultSourceProviderFactory;
        $this->productCollectionFactory                  = $productCollectionFactory;
        $this->categoryRepository                        = $categoryRepository;
        $this->categoryResourceModel                     = $categoryResourceModel;
        $this->ruleCollectionFactory                     = $ruleCollectionFactory;
        $this->replUnitOfMeasureRepository               = $replUnitOfMeasureRepository;
        $this->parentItemProcessor                       = $parentItemProcessor;
        $this->productRepository                         = $productRepository;
        $this->getParentSkusOfChildrenSkus               = $getParentSkusOfChildrenSkus;
        $this->stockStatusRepository                     = $stockStatusRepository;
        $this->getProductIdsBySkus                       = $getProductIdsBySkus;
        $this->eavAttributeFactory                       = $eavAttributeFactory;
        $this->productResourceModel                      = $productResourceModel;
        $this->productMetadata                           = $productMetadata;
        $this->getStockSourceLinks                       = $getStockSourceLinks;
        $this->sourceItemRepository                      = $sourceItemRepository;
        $this->sourceItemDeleteRepository                = $sourceItemsDelete;
        $this->getAssignedStockIdForWebsite              = $getAssignedStockIdForWebsite;
        $this->criteriaInterfaceFactory                  = $criteriaInterfaceFactory;
        $this->stockItemRepository                       = $stockItemRepository;
        $this->stockConfiguration                        = $stockConfiguration;
        $this->isSingleSourceMode                        = $isSingleSourceMode;
        parent::__construct(
            $context
        );
    }

    /**
     * @param string $filtername
     * @param string $filtervalue
     * @param string $conditionType
     * @param int $pagesize
     * @param bool $excludeDeleted
     * @return SearchCriteria
     */
    public function buildCriteriaForNewItems(
        $filtername = '',
        $filtervalue = '',
        $conditionType = 'eq',
        $pagesize = 100,
        $excludeDeleted = true
    ) {
        // creating search criteria for two fields
        // processed = 0 which means not yet processed
        $attr_processed = $this->filterBuilder->setField('processed')
            ->setValue('0')
            ->setConditionType('eq')
            ->create();
        // is_updated = 1 which means may be processed already but is updated on omni end
        $attr_is_updated = $this->filterBuilder->setField('is_updated')
            ->setValue('1')
            ->setConditionType('eq')
            ->create();
        // building OR condition between the above two criteria
        $filterOr = $this->filterGroupBuilder
            ->addFilter($attr_processed)
            ->addFilter($attr_is_updated)
            ->create();
        // adding criteria into where clause.
        $criteria = $this->searchCriteriaBuilder->setFilterGroups([$filterOr]);
        if ($filtername != '' && $filtervalue != '') {
            $criteria->addFilter(
                $filtername,
                $filtervalue,
                $conditionType
            );
        }
        if ($excludeDeleted) {
            $criteria->addFilter('IsDeleted', 0, 'eq');
        }
        if ($pagesize != -1) {
            $criteria->setPageSize($pagesize);
        }
        return $criteria->create();
    }

    /**
     * @param string $filtername
     * @param string $filtervalue
     * @param string $conditionType
     * @param int $pagesize
     * @param bool $excludeDeleted
     * @return SearchCriteria
     */
    public function buildCriteriaForVariantAttributesNewItems(
        $filtername = '',
        $filtervalue = '',
        $conditionType = 'eq',
        $pagesize = 100,
        $excludeDeleted = true
    ) {
        // creating search criteria for two fields
        // processed = 0 which means not yet processed
        $attr_processed = $this->filterBuilder->setField('ready_to_process')
            ->setValue('0')
            ->setConditionType('eq')
            ->create();
        // is_updated = 1 which means may be processed already but is updated on omni end
        $attr_is_updated = $this->filterBuilder->setField('is_updated')
            ->setValue('1')
            ->setConditionType('eq')
            ->create();
        // building OR condition between the above two criteria
        $filterOr = $this->filterGroupBuilder
            ->addFilter($attr_processed)
            ->addFilter($attr_is_updated)
            ->create();
        // adding criteria into where clause.
        $criteria = $this->searchCriteriaBuilder->setFilterGroups([$filterOr]);
        if ($filtername != '' && $filtervalue != '') {
            $criteria->addFilter(
                $filtername,
                $filtervalue,
                $conditionType
            );
        }
        if ($excludeDeleted) {
            $criteria->addFilter('IsDeleted', 0, 'eq');
        }
        if ($pagesize != -1) {
            $criteria->setPageSize($pagesize);
        }
        return $criteria->create();
    }

    /**
     * @param string $item_id
     * @param int $pagesize
     * @param bool $excludeDeleted
     * @return SearchCriteria
     */
    public function buildCriteriaForProductAttributes(
        $item_id = '',
        $pagesize = 100,
        $excludeDeleted = true,
        $scope_id = false
    ) {
        $attr_processed = $this->filterBuilder->setField('processed')
            ->setValue('0')
            ->setConditionType('eq')
            ->create();
        // is_updated = 1 which means may be processed already but is updated on omni end
        $attr_is_updated = $this->filterBuilder->setField('is_updated')
            ->setValue('1')
            ->setConditionType('eq')
            ->create();
        // building OR condition between the above two criteria
        $filterOr = $this->filterGroupBuilder
            ->addFilter($attr_processed)
            ->addFilter($attr_is_updated)
            ->create();
        // adding criteria into where clause.
        $criteria = $this->searchCriteriaBuilder->setFilterGroups([$filterOr]);
        if ($scope_id) {
            $criteria->addFilter('scope_id', $scope_id, 'eq');
        }
        $criteria->addFilter('LinkField1', $item_id, 'eq');

        if ($excludeDeleted) {
            $criteria->addFilter('IsDeleted', 0, 'eq');
        }
        if ($pagesize != -1) {
            $criteria->setPageSize($pagesize);
        }
        return $criteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters
     * @param array $filters
     * @param int $pagesize
     * @param boolean $excludeDeleted
     * @return SearchCriteria
     */
    public function buildCriteriaForArray(
        array $filters,
        $pagesize = 100,
        $excludeDeleted = true,
        $parameter = null,
        $parameter2 = null
    ) {
        $filterOr       = null;
        $attr_processed = $this->filterBuilder->setField('processed')
            ->setValue('0')
            ->setConditionType('eq')
            ->create();
        // is_updated = 1 which means may be processed already but is updated on omni end
        $attr_is_updated = $this->filterBuilder->setField('is_updated')
            ->setValue('1')
            ->setConditionType('eq')
            ->create();

        if (!empty($parameter) && !empty($parameter2)) {
            $parameter1 = $this->filterBuilder->setField($parameter['field'])
                ->setValue($parameter['value'])
                ->setConditionType($parameter['condition_type'])
                ->create();

            $parameter2 = $this->filterBuilder->setField($parameter2['field'])
                ->setValue($parameter2['value'])
                ->setConditionType($parameter2['condition_type'])
                ->create();

            // building OR condition between the above  criteria
            $filterOr = $this->filterGroupBuilder
                ->addFilter($attr_processed)
                ->addFilter($attr_is_updated)
                ->addFilter($parameter1)
                ->addFilter($parameter2)
                ->create();
        } else {
            if (!empty($parameter)) {
                $ExtraFieldwithOrCondition = $this->filterBuilder->setField($parameter['field'])
                    ->setValue($parameter['value'])
                    ->setConditionType($parameter['condition_type'])
                    ->create();

                // building OR condition between the above  criteria
                $filterOr = $this->filterGroupBuilder
                    ->addFilter($attr_processed)
                    ->addFilter($attr_is_updated)
                    ->addFilter($ExtraFieldwithOrCondition)
                    ->create();
            } else {
                // building OR condition between the above two criteria
                $filterOr = $this->filterGroupBuilder
                    ->addFilter($attr_processed)
                    ->addFilter($attr_is_updated)
                    ->create();
            }
        }
        $criteria = $this->searchCriteriaBuilder->setFilterGroups([$filterOr]);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $criteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        if ($excludeDeleted) {
            $criteria->addFilter('IsDeleted', 0, 'eq');
        }
        if ($pagesize != -1) {
            $criteria->setPageSize($pagesize);
        }
        return $criteria->create();
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @param bool $excludeDeleted
     * @param null $parameter
     * @return SearchCriteria
     */
    public function buildCriteriaForArrayFrontEnd(
        array $filters,
        $pageSize = 100,
        $excludeDeleted = true,
        $parameter = null
    ) {
        $filterOr      = null;
        $attrProcessed = $this->filterBuilder->setField('processed')
            ->setValue('1')
            ->setConditionType('eq')
            ->create();
        if (!empty($parameter)) {
            $extraFieldWithOrCondition = $this->filterBuilder->setField($parameter['field'])
                ->setValue($parameter['value'])
                ->setConditionType($parameter['condition_type'])
                ->create();
            // building OR condition between the above  criteria
            $filterOr = $this->filterGroupBuilder
                ->addFilter($attrProcessed)
                ->addFilter($extraFieldWithOrCondition)
                ->create();
        } else {
            $filterOr = $this->filterGroupBuilder
                ->addFilter($attrProcessed)
                ->create();
        }
        $criteria = $this->searchCriteriaBuilder->setFilterGroups([$filterOr]);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $criteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        if ($excludeDeleted) {
            $criteria->addFilter('IsDeleted', 0, 'eq');
        }
        if ($pageSize != -1) {
            $criteria->setPageSize($pageSize);
        }
        return $criteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters
     * @param array $filters
     * @param int $pagesize
     * @param boolean $excludeDeleted
     * @param null $parameter
     * @param null $parameter2
     * @return SearchCriteria
     */
    public function buildCriteriaForDirect(
        array $filters,
        $pagesize = 100,
        $excludeDeleted = true,
        $parameter = null,
        $parameter2 = null
    ) {
        $filterOr = null;
        if (!empty($parameter) && !empty($parameter2)) {
            $parameter1 = $this->filterBuilder->setField($parameter['field'])
                ->setValue($parameter['value'])
                ->setConditionType($parameter['condition_type'])
                ->create();

            $parameter2 = $this->filterBuilder->setField($parameter2['field'])
                ->setValue($parameter2['value'])
                ->setConditionType($parameter2['condition_type'])
                ->create();

            // building OR condition between the above  criteria
            $filterOr = $this->filterGroupBuilder
                ->addFilter($parameter1)
                ->addFilter($parameter2)
                ->create();
        } else {
            if (!empty($parameter)) {
                $ExtraFieldwithOrCondition = $this->filterBuilder->setField($parameter['field'])
                    ->setValue($parameter['value'])
                    ->setConditionType($parameter['condition_type'])
                    ->create();

                // building OR condition between the above  criteria
                $filterOr = $this->filterGroupBuilder
                    ->addFilter($ExtraFieldwithOrCondition)
                    ->create();
            }
        }

        if (!empty($filterOr)) {
            $criteria = $this->searchCriteriaBuilder->setFilterGroups([$filterOr]);
        } else {
            $criteria = $this->searchCriteriaBuilder->setFilterGroups([]);
        }
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $criteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        if ($excludeDeleted) {
            $criteria->addFilter('main_table.IsDeleted', 0, 'eq');
        }
        if ($pagesize != -1) {
            $criteria->setPageSize($pagesize);
        }
        return $criteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters and return Updated Only
     * @param array $filters
     * @param int $pagesize
     * @return SearchCriteria
     */
    public function buildCriteriaGetUpdatedOnly(array $filters, $pagesize = 100, $excludeDeleted = true)
    {
        $criteria = $this->searchCriteriaBuilder;
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $criteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        if ($excludeDeleted) {
            $criteria->addFilter('main_table.IsDeleted', 0, 'eq');
        }
        $criteria->addFilter('main_table.is_updated', 1, 'eq');
        if ($pagesize != -1) {
            $criteria->setPageSize($pagesize);
        }
        return $criteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters and return Updated Only
     * @param array $filters
     * @param int $pagesize
     * @return SearchCriteria
     */
    public function buildCriteriaGetDeletedOnly(array $filters, $pagesize = 100)
    {
        $criteria = $this->searchCriteriaBuilder;
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $criteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        $criteria->addFilter('IsDeleted', 1, 'eq');
        $criteria->addFilter('is_updated', 1, 'eq');
        if ($pagesize != -1) {
            $criteria->setPageSize($pagesize);
        }
        return $criteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters and return Updated Only
     * @param array $filters
     * @param int $pagesize
     * @return SearchCriteria
     */
    public function buildCriteriaGetDeletedOnlyWithAlias(array $filters, $pagesize = 100)
    {
        $criteria = $this->searchCriteriaBuilder;
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $criteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        $criteria->addFilter('main_table.IsDeleted', 1, 'eq');
        $criteria->addFilter('main_table.is_updated', 1, 'eq');
        if ($pagesize != -1) {
            $criteria->setPageSize($pagesize);
        }
        return $criteria->create();
    }

    /**
     * Create Build Exit Criteria with Array of filters as a parameters
     * @param array $filters
     * @param int $pagesize
     * @return SearchCriteria
     */
    public function buildExitCriteriaForArray(array $filters, $pagesize = 1)
    {
        $searchCriteria = $this->searchCriteriaBuilder;
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $searchCriteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }

        if ($pagesize != -1) {
            $searchCriteria->setPageSize($pagesize);
        }
        return $searchCriteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters
     * @param array $filters
     * @param int $pagesize
     * @param boolean $excludeDeleted
     * @return SearchCriteria
     */
    public function buildCriteriaForArrayWithAlias(array $filters, $pagesize = 100, $excludeDeleted = true)
    {
        $attr_processed = $this->filterBuilder->setField('main_table.processed')
            ->setValue('0')
            ->setConditionType('eq')
            ->create();
        // is_updated = 1 which means may be processed already but is updated on omni end
        $attr_is_updated = $this->filterBuilder->setField('main_table.is_updated')
            ->setValue('1')
            ->setConditionType('eq')
            ->create();
        // building OR condition between the above two criteria
        $filterOr = $this->filterGroupBuilder
            ->addFilter($attr_processed)
            ->addFilter($attr_is_updated)
            ->create();
        // adding criteria into where clause.
        $criteria = $this->searchCriteriaBuilder->setFilterGroups([$filterOr]);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $criteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        if ($excludeDeleted) {
            $criteria->addFilter('main_table.IsDeleted', 0, 'eq');
        }

        if ($pagesize != -1) {
            $criteria->setPageSize($pagesize);
        }
        return $criteria->create();
    }

    /**
     * @param string $nav_id
     * @param string $type
     * @param int $includeDeleted
     * @param bool $store_id
     * @return bool|AbstractExtensibleObject[]
     * @throws InputException
     */
    public function getImageLinksByType($nav_id = '', $type = 'Item Category', $includeDeleted = 0, $store_id = false)
    {
        if (empty($nav_id)) {
            return false;
        }
        $criteria = $this->searchCriteriaBuilder->addFilter(
            'KeyValue',
            $nav_id,
            'eq'
        )->addFilter(
            'TableName',
            $type,
            'eq'
        )->addFilter(
            'IsDeleted',
            $includeDeleted,
            'eq'
        );

        if ($store_id) {
            $criteria->addFilter('scope_id', $store_id, 'eq');
        }
        $sortOrder = $this->sortOrder->setField('DisplayOrder')->setDirection(SortOrder::SORT_ASC);
        $criteria->setSortOrders([$sortOrder]);
        /** @var ReplImageLinkSearchResults $items */
        $items = $this->replImageLinkRepositoryInterface->getList($criteria->create());
        if ($items->getTotalCount() > 0) {
            return $items->getItems();
        }
        return false;
    }

    /**
     * @param string $image_id
     * @return Entity\ImageStreamGetByIdResponse|ResponseInterface|null|string
     */
    public function imageStreamById($image_id = '')
    {
        $response = null;
        if ($image_id == '' || $image_id == null) {
            return $response;
        }
        // @codingStandardsIgnoreStart
        $request = new Operation\ImageStreamGetById();
        $entity  = new Entity\ImageStreamGetById();
        // @codingStandardsIgnoreEnd
        $entity->setId($image_id);
        try {
            $response = $request->execute($entity);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * @return null|string
     * @throws LocalizedException
     */
    public function getDefaultAttributeSetId()
    {
        return $this->eavConfig->getEntityType('catalog_product')
            ->getDefaultAttributeSetId();
    }

    /**
     * @param string $attributeSet
     * @return int|null
     */
    public function getDefaultGroupIdOfAttributeSet($attributeSet = '')
    {
        if ($attributeSet == '') {
            $attributeSet = 4;
        }
        return $this->attributeSet->getDefaultGroupId($attributeSet);
    }

    /**
     * Format the Nav attribute code according to Magento without space and lowercase
     * @param $code
     * @return mixed|string
     */
    public function formatAttributeCode($code)
    {
        $code = strtolower(trim($code));
        $code = str_replace(' ', '_', $code);
        // convert all special characters and replace it with _
        $code = preg_replace('/[^a-zA-Z0-9_.]/', '_', $code);
        return 'ls_' . $code;
    }

    /**
     * Format the Item Modifier and Recipe
     * @param $code
     * @return mixed|string
     */
    public function formatMidifier($code)
    {
        $code = strtolower(trim($code));
        $code = str_replace(' ', '_', $code);
        // convert all special characters and replace it with _
        $code = preg_replace('/[^a-zA-Z0-9_.]/', '_', $code);
        return $code;
    }

    /**
     * @return array
     */
    public function getAllWebsitesIds()
    {
        $websiteIds = [];
        $websites   = $this->storeManager->getWebsites();
        /** @var Interceptor $website */
        foreach ($websites as $website) {
            $websiteIds[] = $website->getId();
        }
        return $websiteIds;
    }

    /**
     * Clear the cache by type code
     * @param $typeCode
     */
    public function flushByTypeCode($typeCode)
    {
        $this->cacheTypeList->cleanType($typeCode);
        $this->_logger->debug($typeCode . ' cache type flushed.');
    }

    /**
     * Update the config status and clean cache for config
     * @param $data
     * @param $path
     * @param bool $storeId
     * @param bool $flushCache
     * @param string $scope
     */
    public function updateCronStatus($data, $path, $storeId = false, $flushCache = true, $scope = ScopeInterface::SCOPE_WEBSITES)
    {

        /**
         * add a check here to see if new value is different from old one in order to avoid unnecessory flushing.
         */
        $existingData = $this->lsr->getConfigValueFromDb(
            $path,
            $scope,
            $storeId
        );

        if ($existingData == $data) {
            return;
        } else {
            /**
             * Added the condition to update config value based on specific store id.
             */
            if ($storeId) {
                $this->configWriter->save(
                    $path,
                    ($data) ? 1 : 0,
                    $scope,
                    $storeId
                );
            } else {
                $this->configWriter->save(
                    $path,
                    ($data) ? 1 : 0,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
            }
            if ($flushCache) {
                $this->flushByTypeCode('config');
            }
        }
    }

    /**
     * USE THIS WHEN YOU WANT TO RESET STATUS FOR ALL THE STORES WITHOUT PASSING ANY STORE ID
     * @param $data
     * @param $path
     */
    public function updateCronStatusForAllStores($data, $path)
    {
        $stores = $this->lsr->getAllStores();
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->configWriter->save(
                    $path,
                    ($data) ? 1 : 0,
                    ScopeInterface::SCOPE_STORES,
                    $store->getId()
                );
            }
        }
    }

    /**
     * Update the config value
     * @param $value
     * @param $path
     * @param bool $storeId
     * @param string $scope
     */
    public function updateConfigValue($value, $path, $storeId = false, $scope = ScopeInterface::SCOPE_WEBSITES)
    {
        /**
         * Added the condition to update config value based on specific store id.
         */
        if ($storeId) {
            $this->configWriter->save(
                $path,
                $value,
                $scope,
                $storeId
            );
        } else {
            $this->configWriter->save(
                $path,
                $value,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * This websiteId is the id of scope website in the Magento system.
     * and webStore is the LS Central store id stored in the core_config_data
     * Trigger the disposable Hierarchy replication job to get Hierarchy based on stores.
     * @param string $websiteId
     * @return array|Entity\ReplEcommHierarchyResponse|Entity\ReplHierarchyResponse|ResponseInterface
     */
    public function getHierarchyByStore($websiteId = '')
    {
        $response = [];

        $webStore = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $websiteId);
        $base_url = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_BASE_URL, $websiteId);
        $lsKey    = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_LS_KEY, $websiteId);
        // @codingStandardsIgnoreStart
        $hierarchy = new Entity\ReplEcommHierarchy();

        $request   = new Entity\ReplRequest();
        $operation = new Operation\ReplEcommHierarchy($base_url);
        $operation->setToken($lsKey);
        // @codingStandardsIgnoreEnd

        $request->setStoreId($webStore)
            ->setBatchSize(100)
            ->setFullReplication(true)
            ->setLastKey(0)
            ->setMaxKey(0)
            ->setTerminalId('');

        $this->_logger->debug(var_export($operation->getResponse(), true));

        try {
            $response = $operation->execute($hierarchy->setReplRequest($request));
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * For getting tax setup information
     * @return array|null
     */
    public function getTaxSetup()
    {
        $items   = null;
        $filters = [
            ['field' => 'ProductTaxGroup', 'value' => '', 'condition_type' => 'neq'],
            ['field' => 'BusinessTaxGroup', 'value' => '', 'condition_type' => 'neq'],
            ['field' => 'TaxPercent', 'value' => 0, 'condition_type' => 'gt']
        ];

        $searchCriteria = $this->buildCriteriaForDirect($filters, -1, true);

        try {
            $items = $this->replTaxSetupRepository->getList($searchCriteria)->getItems();
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return $items;
    }

    /**
     * For getting tender type information
     *
     * @param $scopeId
     * @return null|array
     */
    public function getTenderTypes($scopeId)
    {
        $items   = null;
        $filters = [
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq']
        ];

        $searchCriteria = $this->buildCriteriaForDirect($filters, -1, true);

        try {
            $items = $this->replStoreTenderTypeRepository->getList($searchCriteria)->getItems();
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return $items;
    }

    /**
     * @param $collection
     * @param SearchCriteriaInterface $criteria
     * @param $primaryTableColumnName
     * @param $secondaryTableName
     * @param $secondaryTableColumnName
     * @param bool $group
     * @param bool $isReplaceJoin
     * @param bool $isCatJoin
     * @param null $websiteId
     */
    public function setCollectionPropertiesPlusJoin(
        &$collection,
        SearchCriteriaInterface $criteria,
        $primaryTableColumnName,
        $secondaryTableName,
        $secondaryTableColumnName,
        $group = false,
        $isReplaceJoin = false,
        $isCatJoin = false,
        $websiteId = null
    ) {
        $this->setFiltersOnTheBasisOfCriteria($collection, $criteria);
        $this->setSortOrdersOnTheBasisOfCriteria($collection, $criteria);
        $second_table_name = $this->resource->getTableName($secondaryTableName);
        // @codingStandardsIgnoreStart
        // In order to only select those records whose items are available
        if ($isReplaceJoin) {
            $collection->getSelect()->joinInner(
                ['second' => $second_table_name],
                'main_table.' . $primaryTableColumnName . ' = REPLACE(second.' . $secondaryTableColumnName . ',"-",",")',
                []
            );
        } elseif ($isCatJoin) {
            $hierarchyCode = $this->lsr->getWebsiteConfig(LSR::SC_REPLICATION_HIERARCHY_CODE, $websiteId);
            $hierarchyCode = $hierarchyCode . ';';
            $collection->getSelect()->joinInner(
                ['second' => $second_table_name],
                'REPLACE(main_table.' . $primaryTableColumnName . ',"' . $hierarchyCode . '","")= second.' . $secondaryTableColumnName,
                []
            );
            $collection->getSelect()->columns('second.' . $secondaryTableColumnName);
        } else {
            $collection->getSelect()->joinInner(
                ['second' => $second_table_name],
                'main_table.' . $primaryTableColumnName . ' = second.' . $secondaryTableColumnName,
                []
            );
        }
        if ($group) {
            $collection->getSelect()->group('main_table.' . $primaryTableColumnName);
        }
        /** For Xdebug only to check the query */
        $query = $collection->getSelect()->__toString();
        // @codingStandardsIgnoreEnd
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
    }

    /**
     * Set collection properties plus join sku
     *
     * @param mixed $collection
     * @param SearchCriteriaInterface $criteria
     * @param string $primaryTableColumnName
     * @param string $primaryTableColumnName2
     * @param array $groupColumns
     * @return void
     * @throws LocalizedException
     */
    public function setCollectionPropertiesPlusJoinSku(
        &$collection,
        SearchCriteriaInterface $criteria,
        $primaryTableColumnName,
        $primaryTableColumnName2,
        $groupColumns = []
    ) {
        $this->setFiltersOnTheBasisOfCriteria($collection, $criteria);
        $this->setSortOrdersOnTheBasisOfCriteria($collection, $criteria);
        // @codingStandardsIgnoreStart
        // In order to only select those records whose items are available
        if (!empty($primaryTableColumnName2)) {
            $this->applyItemIdAndVariantIdJoins(
                $collection,
                'main_table',
                $primaryTableColumnName,
                $primaryTableColumnName2,
                $groupColumns
            );
        } else {
            $this->applyItemIdJoin($collection, 'main_table', $primaryTableColumnName, $groupColumns);
        }
        /** For Xdebug only to check the query */
        $query = $collection->getSelect()->__toString();
        // @codingStandardsIgnoreEnd
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
    }

    /**
     * Apply product website join
     * Must have applied itemIdTable join before using this
     * @param $collection
     * @param $websiteId
     * @return void
     */
    public function applyProductWebsiteJoin(&$collection, $websiteId)
    {
        $itemIdTableAlias = self::ITEM_ID_TABLE_ALIAS;

        $collection->getSelect()->joinInner(
            ['cpw' => 'catalog_product_website'],
            $this->magentoEditionSpecificJoinWhereClause(
                "cpw.product_id = $itemIdTableAlias.entity_id" .
                " AND cpw.website_id = $websiteId",
                'catalog_product_entity_varchar',
                [$itemIdTableAlias]
            ),
            []
        );
    }

    /**
     * Set collection joins for inventory
     *
     * @param mixed $collection
     * @param SearchCriteriaInterface $criteria
     * @return void
     * @throws LocalizedException
     */
    public function setCollectionPropertiesPlusJoinsForInventory(&$collection, SearchCriteriaInterface $criteria)
    {
        $thirdTableName = $this->resource->getTableName('ls_replication_repl_item');
        $this->setFiltersOnTheBasisOfCriteria($collection, $criteria);
        $this->setSortOrdersOnTheBasisOfCriteria($collection, $criteria);
        $collection->getSelect()->joinInner(
            ['third' => $thirdTableName],
            'main_table.ItemId' . ' = third.nav_id' . ' AND main_table.scope_id' . ' = third.scope_id',
            []
        );

        $this->applyItemIdAndVariantIdJoins(
            $collection,
            'main_table',
            'ItemId',
            'VariantId',
            ['repl_inv_status_id']
        );
        /** For Xdebug only to check the query */
        $query = $collection->getSelect()->__toString();
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
    }

    /**
     * Apply joins for lsr_item_id, lsr_variant_id custom attributes
     *
     * @param mixed $collection
     * @param string $mainTableAlias
     * @param string $mainTableItemIdColumn
     * @param string $mainTableVariantIdColumn
     * @param array $groupColumns
     * @return void
     * @throws LocalizedException
     */
    public function applyItemIdAndVariantIdJoins(
        &$collection,
        $mainTableAlias,
        $mainTableItemIdColumn,
        $mainTableVariantIdColumn,
        $groupColumns = []
    ) {
        $variantIdTableAlias = self::VARIANT_ID_TABLE_ALIAS;

        $this->applyItemIdJoin($collection, $mainTableAlias, $mainTableItemIdColumn);
        $this->applyVariantdJoin($collection, $mainTableAlias, $mainTableVariantIdColumn);
        /**
         * @codingStandardsIgnoreStart
         */
        $collection->getSelect()->where("IF (main_table.$mainTableVariantIdColumn IS NOT NULL AND ($variantIdTableAlias.value IS NULL OR $variantIdTableAlias.value = ''),0,1)");
        /**
         * @codingStandardsIgnoreEnd
         */
        foreach ($groupColumns as $groupColumn) {
            $collection->getSelect()->group("$mainTableAlias.$groupColumn");
        }
    }

    /**
     * Replace column names of joining tables based on mapping
     *
     * @param $whereClause
     * @param $tableName
     * @param $aliasNames
     * @return array|mixed|string|string[]
     */
    public function magentoEditionSpecificJoinWhereClause(
        $whereClause,
        $tableName,
        $aliasNames
    ) {
        if ($this->productMetadata->getEdition() != ProductMetadata::EDITION_NAME &&
            isset(self::COLUMNS_MAPPING[$tableName])
        ) {
            $mappingColumns = self::COLUMNS_MAPPING[$tableName];

            foreach ($aliasNames as $alias) {
                foreach ($mappingColumns as $columnName => $newColumnName) {
                    $whereClause = str_replace(
                        "$alias.$columnName",
                        "$alias.$newColumnName",
                        $whereClause
                    );
                }
            }
        }

        return $whereClause;
    }

    /**
     * Apply join for lsr_item_id custom attribute
     *
     * @param mixed $collection
     * @param mixed $mainTableAlias
     * @param mixed $mainTableItemIdColumn
     * @param array $groupColumns
     * @return void
     * @throws LocalizedException
     */
    public function applyItemIdJoin($collection, $mainTableAlias, $mainTableItemIdColumn, $groupColumns = [])
    {
        $itemIdTableAlias = self::ITEM_ID_TABLE_ALIAS;
        $itemAttributeId  = $this->eavConfig->getAttribute(
            'catalog_product',
            LSR::LS_ITEM_ID_ATTRIBUTE_CODE
        )->getId();

        $collection->getSelect()->joinInner(
            [self::ITEM_ID_TABLE_ALIAS => 'catalog_product_entity_varchar'],
            $this->magentoEditionSpecificJoinWhereClause(
                "$mainTableAlias.$mainTableItemIdColumn = $itemIdTableAlias.value" .
                " AND $itemIdTableAlias.attribute_id = $itemAttributeId",
                'catalog_product_entity_varchar',
                [$itemIdTableAlias]
            ),
            []
        );

        foreach ($groupColumns as $groupColumn) {
            $collection->getSelect()->group("$mainTableAlias.$groupColumn");
        }
    }

    /**
     * Apply join for lsr_variant_id custom attribute
     *
     * @param mixed $collection
     * @param mixed $mainTableAlias
     * @param mixed $mainTableVariantIdColumn
     * @return void
     * @throws LocalizedException
     */
    public function applyVariantdJoin($collection, $mainTableAlias, $mainTableVariantIdColumn)
    {
        $itemIdTableAlias    = self::ITEM_ID_TABLE_ALIAS;
        $variantIdTableAlias = self::VARIANT_ID_TABLE_ALIAS;
        $variantAttributeId  = $this->eavConfig->getAttribute(
            'catalog_product',
            LSR::LS_VARIANT_ID_ATTRIBUTE_CODE
        )->getId();

        $collection->getSelect()->joinLeft(
            [$variantIdTableAlias => 'catalog_product_entity_varchar'],
            $this->magentoEditionSpecificJoinWhereClause(
                "$mainTableAlias.$mainTableVariantIdColumn = $variantIdTableAlias.value" .
                " AND $itemIdTableAlias.entity_id = $variantIdTableAlias.entity_id" .
                " AND $variantIdTableAlias.attribute_id = $variantAttributeId" .
                " AND $itemIdTableAlias.store_id = $variantIdTableAlias.store_id",
                'catalog_product_entity_varchar',
                [$itemIdTableAlias, $variantIdTableAlias]
            ),
            []
        );
    }

    /**
     * Fetch required records to update for vendor attributes
     *
     * @param mixed $collection
     * @param SearchCriteriaInterface $criteria
     * @return void
     * @throws LocalizedException
     */
    public function setCollectionPropertiesPlusJoinsForVendor(&$collection, SearchCriteriaInterface $criteria)
    {
        $thirdTableName = $this->resource->getTableName('ls_replication_repl_vendor');
        $this->setFiltersOnTheBasisOfCriteria($collection, $criteria);
        $this->setSortOrdersOnTheBasisOfCriteria($collection, $criteria);
        $collection->getSelect()->joinInner(
            ['third' => $thirdTableName],
            'main_table.NavManufacturerId' . ' = third.nav_id' . ' AND main_table.scope_id' . ' = third.scope_id' .
            ' AND third.processed' . ' = 1',
            []
        );

        $this->applyItemIdJoin($collection, 'main_table', 'NavProductId', ['repl_loy_vendor_item_mapping_id']);
        $collection->getSelect()->columns('third.name');
        /** For Xdebug only to check the query $query */
        $query = $collection->getSelect()->__toString();
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
    }

    /**
     * Fetch all required standard variant records
     *
     * @param mixed $collection
     * @param SearchCriteriaInterface $criteria
     * @param bool $joinCatalogTable
     * @throws LocalizedException
     */
    public function setCollectionForStandardVariants(
        &$collection,
        SearchCriteriaInterface $criteria,
        $joinCatalogTable = false
    ) {
        if ($joinCatalogTable) {
            $this->setCollectionPropertiesPlusJoinSku($collection, $criteria, 'ItemId', null, ['repl_item_variant_id']);
        } else {
            $this->setFiltersOnTheBasisOfCriteria($collection, $criteria);
            $this->setSortOrdersOnTheBasisOfCriteria($collection, $criteria);
        }

        $secondTableName = $this->resource->getTableName('ls_replication_repl_item_variant_registration');
        $collection
            ->getSelect()->where('ItemId NOT IN (?)', new \Zend_Db_Expr("select ItemId From $secondTableName"));
        /** For Xdebug only to check the query $query */
        $query = $collection->getSelect()->__toString();
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
    }

    /**
     * @param $collection
     * @param SearchCriteriaInterface $criteria
     * @param string $type
     * @return void
     * @throws LocalizedException
     */
    public function setCollectionPropertiesPlusJoinsForImages(&$collection, SearchCriteriaInterface $criteria, $type)
    {
        $itemIdTableAlias    = self::ITEM_ID_TABLE_ALIAS;
        $variantIdTableAlias = self::VARIANT_ID_TABLE_ALIAS;

        if ($type == 'Item') {
            $thirdTableName = $this->resource->getTableName('ls_replication_repl_item');
        } else {
            $thirdTableName = $this->resource->getTableName('ls_replication_repl_hierarchy_leaf');
        }

        $this->setFiltersOnTheBasisOfCriteria($collection, $criteria);
        $this->setSortOrdersOnTheBasisOfCriteria($collection, $criteria);
        $itemAttributeId    = $this->eavConfig->getAttribute(
            'catalog_product',
            LSR::LS_ITEM_ID_ATTRIBUTE_CODE
        )->getId();
        $variantAttributeId = $this->eavConfig->getAttribute(
            'catalog_product',
            LSR::LS_VARIANT_ID_ATTRIBUTE_CODE
        )->getId();

        $collection->getSelect()->joinInner(
            [$itemIdTableAlias => 'catalog_product_entity_varchar'],
            $this->magentoEditionSpecificJoinWhereClause(
                "SUBSTRING_INDEX(main_table.KeyValue, ',', 1)  = $itemIdTableAlias.value" .
                " AND $itemIdTableAlias.attribute_id = $itemAttributeId",
                'catalog_product_entity_varchar',
                [$itemIdTableAlias]
            ),
            []
        );

        $collection->getSelect()->joinLeft(
            [$variantIdTableAlias => 'catalog_product_entity_varchar'],
            $this->magentoEditionSpecificJoinWhereClause(
                "SUBSTRING_INDEX (main_table.KeyValue, ',', - 1)  = $variantIdTableAlias.value" .
                " AND $itemIdTableAlias.entity_id = $variantIdTableAlias.entity_id" .
                " AND $variantIdTableAlias.attribute_id = $variantAttributeId" .
                " AND $itemIdTableAlias.store_id = $variantIdTableAlias.store_id",
                'catalog_product_entity_varchar',
                [$itemIdTableAlias, $variantIdTableAlias]
            ),
            []
        );

        $collection->getSelect()->joinInner(
            ['third' => $thirdTableName],
            'third.nav_id' . ' = SUBSTRING_INDEX(main_table.KeyValue,",",1)' . '
            AND main_table.scope_id' . ' = third.scope_id',
            []
        );

        if ($type == 'Item') {
            //@codingStandardsIgnoreLine
            $collection->getSelect()->where("IF (main_table.TableName = 'Item Variant' AND $variantIdTableAlias.value IS NOT NULL,1,0) OR IF (main_table.TableName = 'Item' AND $variantIdTableAlias.value IS NULL,1,0)");
        }
        $collection->getSelect()->group("main_table.repl_image_link_id");
        /** For Xdebug only to check the query $query */
        $query = $collection->getSelect()->__toString();
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
    }

    /**
     * Set collection properties and join for product attribute values data translation
     *
     * In order to select only processed entries
     *
     * @param mixed $collection
     * @param SearchCriteriaInterface $criteria
     * @throws LocalizedException
     */
    public function setCollectionPropertiesPlusJoinsForProductAttributeValuesDataTranslation(
        &$collection,
        SearchCriteriaInterface $criteria
    ) {
        $itemIdTableAlias    = self::ITEM_ID_TABLE_ALIAS;
        $variantIdTableAlias = self::VARIANT_ID_TABLE_ALIAS;
        $this->setFiltersOnTheBasisOfCriteria($collection, $criteria);
        $this->setSortOrdersOnTheBasisOfCriteria($collection, $criteria);
        $itemAttributeId    = $this->eavConfig->getAttribute(
            'catalog_product',
            LSR::LS_ITEM_ID_ATTRIBUTE_CODE
        )->getId();
        $variantAttributeId = $this->eavConfig->getAttribute(
            'catalog_product',
            LSR::LS_VARIANT_ID_ATTRIBUTE_CODE
        )->getId();

        /**
         * @codingStandardsIgnoreStart
         */
        $collection->getSelect()->joinInner(
            [$itemIdTableAlias => 'catalog_product_entity_varchar'],
            $this->magentoEditionSpecificJoinWhereClause(
                "SUBSTRING_INDEX(REPLACE(REPLACE(SUBSTRING_INDEX (main_table.Key, ';', 3), 'Variant;', ''), 'Item;',''), ';',1) = $itemIdTableAlias.value" .
                " AND $itemIdTableAlias.attribute_id = $itemAttributeId",
                'catalog_product_entity_varchar',
                [$itemIdTableAlias]
            ),
            []
        );

        $collection->getSelect()->joinLeft(
            [$variantIdTableAlias => 'catalog_product_entity_varchar'],
            $this->magentoEditionSpecificJoinWhereClause(
                "SUBSTRING_INDEX(REPLACE(REPLACE(SUBSTRING_INDEX (main_table.Key, ';', 3), 'Variant;', ''), 'Item;',''), ';',-1)  = $variantIdTableAlias.value" .
                " AND $itemIdTableAlias.entity_id = $variantIdTableAlias.entity_id" .
                " AND $variantIdTableAlias.attribute_id = $variantAttributeId" .
                " AND $itemIdTableAlias.store_id = $variantIdTableAlias.store_id",
                'catalog_product_entity_varchar',
                [$itemIdTableAlias, $variantIdTableAlias]
            ),
            []
        );

        $collection->getSelect()->where("IF (SUBSTRING_INDEX (main_table.Key, ';', 1) = 'Variant' AND $variantIdTableAlias.value IS NOT NULL,1,0) OR IF (SUBSTRING_INDEX (main_table.Key, ';', 1)  = 'Item' AND $variantIdTableAlias.value IS NULL,1,0)");
        /**
         * @codingStandardsIgnoreEnd
         */
        $collection->getSelect()->group("main_table.repl_data_translation_id");
        /** For Xdebug only to check the query $query */
        $query = $collection->getSelect()->__toString();
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
    }

    /**
     * Set collection properties and join for hierarchy nodes data translation
     *
     * In order to select only processed entries
     *
     * @param mixed $collection
     * @param SearchCriteriaInterface $criteria
     * @param $websiteId
     * @param $store
     */
    public function setCollectionPropertiesPlusJoinsForHierarchyNodesDataTranslation(
        &$collection,
        SearchCriteriaInterface $criteria,
        $websiteId,
        $store
    ) {
        $this->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'key',
            'catalog_category_entity_varchar',
            'value',
            false,
            false,
            true,
            $websiteId
        );
        $rootCategoryId = $store->getRootCategoryId();
        $collection->getSelect()->joinInner(
            ['third' => 'catalog_category_entity'],
            "second.entity_id = third.entity_id AND third.path LIKE '1/{$rootCategoryId}/%'",
            []
        );

        /** For Xdebug only to check the query $query */
        $query = $collection->getSelect()->__toString();
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
    }

    /**
     * @param $collection
     * @param SearchCriteriaInterface $criteria
     */
    public function setFiltersOnTheBasisOfCriteria(&$collection, SearchCriteriaInterface $criteria)
    {
        foreach ($criteria->getFilterGroups() as $filter_group) {
            $fields = $conditions = [];
            foreach ($filter_group->getFilters() as $filter) {
                $condition    = $filter->getConditionType() ?: 'eq';
                $fields[]     = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
    }

    /**
     * @param $collection
     * @param SearchCriteriaInterface $criteria
     */
    public function setSortOrdersOnTheBasisOfCriteria(&$collection, SearchCriteriaInterface $criteria)
    {
        $sort_orders = $criteria->getSortOrders();
        if ($sort_orders) {
            /** @var SortOrder $sort_order */
            foreach ($sort_orders as $sort_order) {
                $collection->addOrder(
                    $sort_order->getField(),
                    ($sort_order->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
    }

    /**
     * @param $collection
     * @param SearchCriteriaInterface $criteria
     * @param $resultFactory
     * @param null $fieldToSelect
     * @return mixed
     */
    public function setCollection(
        $collection,
        SearchCriteriaInterface $criteria,
        $resultFactory,
        $fieldToSort = null
    ) {
        foreach ($criteria->getFilterGroups() as $filter_group) {
            $fields = $conditions = [];
            foreach ($filter_group->getFilters() as $filter) {
                $condition    = $filter->getConditionType() ?: 'eq';
                $fields[]     = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        if ($fieldToSort) {
            $collection->getSelect()->order('main_table.' . $fieldToSort);
            $collection->getSelect()->order('main_table.QtyPrUom');
        }

        // @codingStandardsIgnoreEnd
        $collection->getSelect()->limit($criteria->getPageSize());

        /** @var For Xdebug only to check the query $query */
        //$query = $collection->getSelect()->__toString();

        $objects = [];
        foreach ($collection as $object_model) {
            $objects[] = $object_model;
        }
        $resultFactory->setItems($objects);
        $resultFactory->setItems($objects);

        return $resultFactory->getItems();
    }

    /**
     * To be used only for Processing attributes and variants in the AttributeCreate Task
     * @return string
     */
    public function getProductAttributeBatchSize()
    {
        return $this->lsr->getStoreConfig(LSR::SC_REPLICATION_PRODUCT_ATTRIBUTE_BATCH_SIZE);
    }

    /**
     * @return string
     */
    public function getDiscountsBatchSize()
    {
        return $this->lsr->getStoreConfig(LSR::SC_REPLICATION_DISCOUNT_BATCH_SIZE);
    }

    /**
     * @return string
     */
    public function getProductInventoryBatchSize()
    {
        return $this->lsr->getStoreConfig(LSR::SC_REPLICATION_PRODUCT_INVENTORY_BATCH_SIZE);
    }

    /**
     * @return string
     */
    public function getProductPricesBatchSize()
    {
        return $this->lsr->getStoreConfig(LSR::SC_REPLICATION_PRODUCT_PRICES_BATCH_SIZE);
    }

    /**
     * @return string
     */
    public function getProductImagesBatchSize()
    {
        return $this->lsr->getStoreConfig(LSR::SC_REPLICATION_PRODUCT_IMAGES_BATCH_SIZE);
    }

    /**
     * @return string
     */
    public function getProductBarcodeBatchSize()
    {
        return $this->lsr->getStoreConfig(LSR::SC_REPLICATION_PRODUCT_BARCODE_BATCH_SIZE);
    }

    /**
     * To be used only for creating variants based products.
     * @return string
     */
    public function getVariantBatchSize()
    {
        return $this->lsr->getStoreConfig(LSR::SC_REPLICATION_VARIANT_BATCH_SIZE);
    }

    /**
     * @return string
     */
    public function getProductCategoryAssignmentBatchSize()
    {
        return $this->lsr->getStoreConfig(LSR::SC_REPLICATION_PRODUCT_ASSIGNMENT_TO_CATEGORY_BATCH_SIZE);
    }

    /**
     * @return string
     */
    public function getMediaPathtoStore()
    {
        return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
    }

    /**
     * @param $mediaDirectory
     */
    public function removeDirectory($mediaDirectory)
    {
        try {
            if ($this->fileSystemDirectory->exists($mediaDirectory)) {
                $this->fileSystemDirectory->remove($mediaDirectory);
            }
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
    }

    /**
     * Check if given mime type is valid
     *
     * @param string $mimeType
     * @return bool
     */
    public function isMimeTypeValid($mimeType)
    {
        return in_array($mimeType, $this->defaultMimeTypes);
    }

    /** return SortOrder object based on the parameters provided
     * @param $field
     * @param string $direction
     * @return SortOrder
     * @throws InputException
     */
    public function getSortOrderObject($field = 'DisplayOrder', $direction = SortOrder::SORT_ASC)
    {
        return $this->sortOrder->setField($field)->setDirection($direction);
    }

    /**
     * @return string
     */
    public function getDatetime()
    {
        return $this->dateTime->gmtDate();
    }

    /**
     * @param $dataTime
     * @param null $format
     * @return string
     * @throws Exception
     */
    public function convertDateTimeIntoCurrentTimeZone($dataTime, $format = null)
    {
        $formattedDate = "";
        if (isset($dataTime)
            && $dataTime !== "0000-00-00 00:00:00"
        ) {
            $date = $this->timezone->date(new \DateTime($dataTime));
            if ($format === null) {
                $format = 'Y-m-d H:i:s';
            }
            $formattedDate = $date->format($format);
        }
        return $formattedDate;
    }

    /**
     * To set the environment variables for cron jobs
     */
    public function setEnvVariables()
    {
        $val1 = ini_get('max_execution_time');
        $val2 = ini_get('memory_limit');
        $this->_logger->debug('ENV Variables Values before:' . $val1 . ' ' . $val2);
        // @codingStandardsIgnoreStart
        @ini_set('max_execution_time', 3600);
        @ini_set('memory_limit', -1);
        // @codingStandardsIgnoreEnd
        $val1 = ini_get('max_execution_time');
        $val2 = ini_get('memory_limit');
        $this->_logger->debug('ENV Variables Values after:' . $val1 . ' ' . $val2);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getCurrentDate()
    {
        $format      = LSR::DATE_FORMAT;
        $currentDate = $this->convertDateTimeIntoCurrentTimeZone(
            $this->getDatetime(),
            $format
        );
        return $currentDate;
    }

    /**
     * Delete url rewrites for given type and scope_id
     *
     * @param $type
     * @param $scopeId
     * @return void
     */
    public function resetUrlRewriteByType($type = '', $scopeId = '')
    {
        if ($type && in_array($type, $this->allowedUrlTypes)) {
            $conditions['entity_type = ?'] = $type;

            if (!empty($scopeId)) {
                $conditions['store_id = ?'] = $scopeId;
            }
            $this->deleteGivenTableDataGivenConditions(
                $this->resource->getTableName('url_rewrite'),
                $conditions
            );
        }
    }

    /**
     * @return string
     */
    public function getAttributeSetsMechanism()
    {
        return $this->lsr->getStoreConfig(LSR::SC_REPLICATION_ATTRIBUTE_SETS_MECHANISM);
    }

    /**
     * @param $itemId
     * @return mixed
     */
    public function getBaseUnitOfMeasure($itemId)
    {
        $filters  = [
            ['field' => 'nav_id', 'value' => $itemId, 'condition_type' => 'eq']
        ];
        $criteria = $this->buildCriteriaForDirect($filters, 1);
        $items    = $this->itemRepository->getList($criteria)->getItems();
        foreach ($items as $item) {
            return $item->getBaseUnitOfMeasure();
        }

        return null;
    }

    /**
     * Assigning configurable product tax class to associated products
     *
     * @param $product
     * @param $taxClass
     * @param $storeId
     * @return void
     */
    public function assignTaxClassToChildren($product, $taxClass, $storeId): void
    {
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $children = $product->getTypeInstance(true)->getUsedProducts($product);
            try {
                foreach ($children as $child) {
                    $childObj = $this->productRepository->get($child->getSku(), true, 0);
                    $childObj->setData('tax_class_id', $taxClass->getClassId());
                    $this->productResourceModel->saveAttribute($childObj, 'tax_class_id');
                }
            } catch (Exception $e) {
                $this->_logger->info("Product tax class update failed for " . $product->getSku());
            }
        }
    }

    /**
     * Assigning product to category
     *
     * @param $product
     * @param $store
     * @throws LocalizedException
     */
    public function assignProductToCategories(&$product, $store)
    {
        $hierarchyCode = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE, $store->getId());
        if (empty($hierarchyCode)) {
            $this->_logger->debug('Hierarchy Code not defined in the configuration for store '
                . $store->getName());
            return;
        }
        $filters              = [
            ['field' => 'NodeId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $store->getWebsiteId(), 'condition_type' => 'eq'],
            [
                'field' => 'nav_id',
                'value' => $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE), 'condition_type' => 'eq'
            ]
        ];
        $criteria             = $this->buildCriteriaForDirect($filters);
        $hierarchyLeafs       = $this->replHierarchyLeafRepository->getList($criteria);
        $resultantCategoryIds = [];
        foreach ($hierarchyLeafs->getItems() as $hierarchyLeaf) {
            $categoryIds = $this->findCategoryIdFromFactory($hierarchyLeaf->getNodeId(), $store);
            if (!empty($categoryIds)) {
                // @codingStandardsIgnoreLine
                $resultantCategoryIds = array_unique(array_merge($resultantCategoryIds, $categoryIds));
            } else {
                $hierarchyLeaf->setData('is_failed', 1);
            }
            $hierarchyLeaf->setData('processed_at', $this->getDateTime());
            $hierarchyLeaf->setData('processed', 1);
            $hierarchyLeaf->setData('is_updated', 0);
            $this->replHierarchyLeafRepository->save($hierarchyLeaf);
        }
        if (!empty($resultantCategoryIds)) {
            try {
                $resultantCategoryIds = array_unique(array_merge($resultantCategoryIds, $product->getCategoryIds()));
                $this->categoryLinkManagement->assignProductToCategories(
                    $product->getSku(),
                    $resultantCategoryIds
                );

                if ($product->getTypeId() == Configurable::TYPE_CODE) {
                    $children = $product->getTypeInstance()->getUsedProducts($product);

                    foreach ($children as $child) {
                        $this->categoryLinkManagement->assignProductToCategories(
                            $child->getSku(),
                            $resultantCategoryIds
                        );
                    }
                }
            } catch (Exception $e) {
                $this->_logger->info("Product deleted from admin configuration. Things will re-run again");
            }
        }
    }

    /**
     * Getting product category id
     *
     * @param $productGroupId
     * @param $store
     * @return array
     * @throws LocalizedException
     */
    public function findCategoryIdFromFactory($productGroupId, $store)
    {
        $categoryCollection = $this->categoryCollectionFactory->create()->addAttributeToFilter(
            'nav_id',
            $productGroupId
        )
            ->addPathsFilter('1/' . $store->getRootCategoryId() . '/')
            ->setPageSize(1);
        if ($categoryCollection->getSize()) {
            // @codingStandardsIgnoreStart
            return [
                $categoryCollection->getFirstItem()->getParentId(),
                $categoryCollection->getFirstItem()->getId()
            ];
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * Utility function to format given input
     *
     * @param $string
     * @return string
     */
    public function oSlug($string)
    {
        // @codingStandardsIgnoreStart
        return strtolower(trim(preg_replace(
            '~[^0-9a-z]+~i',
            '-',
            html_entity_decode(
                preg_replace(
                    '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i',
                    '$1',
                    htmlentities($string, ENT_QUOTES, 'UTF-8')
                ),
                ENT_QUOTES,
                'UTF-8'
            )
        ), '-'));
        // @codingStandardsIgnoreEnd
    }

    /**
     * Getting attribute set id for the given item
     *
     * @param $attributeSetsMechanism
     * @param $joiningTableName
     * @param $storeId
     * @param $identifier
     * @return int|null
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws LocalizedException
     */
    public function getAttributeSetId($attributeSetsMechanism, $joiningTableName, $storeId, $identifier)
    {
        $formattedIdentifier = $this->formatAttributeCode($identifier);
        if ($attributeSet = $this->getAttributeSetByName($formattedIdentifier)) {
            $attributeSetId = $attributeSet;
        } else {
            $attributes     = $this->getRelatedAttributesAssignedToGivenIdentifier(
                $attributeSetsMechanism,
                $joiningTableName,
                $storeId,
                $identifier
            );
            $attributeSetId = $this->createAttributeSetAndGroupsAndReturnAttributeSetId(
                $formattedIdentifier,
                $attributes
            );
        }
        return $attributeSetId;
    }

    /**
     * Getting all soft and hard attribute depending upon current configuration
     *
     * @param $attributeSetsMechanism
     * @param $joiningTableName
     * @param $storeId
     * @param $identifier
     * @return array
     */
    public function getRelatedAttributesAssignedToGivenIdentifier(
        $attributeSetsMechanism,
        $joiningTableName,
        $storeId,
        $identifier
    ) {
        $attributes = [];
        if ($joiningTableName == 'ls_replication_repl_item') {
            if ($attributeSetsMechanism == LSR::SC_REPLICATION_ATTRIBUTE_SET_ITEM_CATEGORY_CODE) {
                if ($identifier == LSR::SC_REPLICATION_ATTRIBUTE_SET_EXTRAS . '_' . $storeId) {
                    $filter = ['field' => 'second.ItemCategoryCode', 'value' => true, 'condition_type' => 'null'];
                } else {
                    $filter = ['field' => 'second.ItemCategoryCode', 'value' => $identifier, 'condition_type' => 'eq'];
                }
            } else {
                if ($identifier == LSR::SC_REPLICATION_ATTRIBUTE_SET_EXTRAS . '_' . $storeId) {
                    $filter = ['field' => 'second.ProductGroupId', 'value' => true, 'condition_type' => 'null'];
                } else {
                    $filter = ['field' => 'second.ProductGroupId', 'value' => $identifier, 'condition_type' => 'eq'];
                }
            }
        } else {
            $filter = ['field' => 'Type', 'value' => 'Deal', 'condition_type' => 'eq'];
        }

        $filters     = [$filter];
        $criteria    = $this->buildCriteriaForDirect($filters, -1, false);
        $collection1 = $this->replAttributeValueCollectionFactory->create();
        $collection2 = $this->replExtendedVariantValueCollectionFactory->create();
        $this->setCollectionPropertiesPlusJoin(
            $collection1,
            $criteria,
            'LinkField1',
            $joiningTableName,
            'nav_id'
        );
        $this->setCollectionPropertiesPlusJoin(
            $collection2,
            $criteria,
            'ItemId',
            $joiningTableName,
            'nav_id'
        );
        $collection1->addFieldToSelect('Code');
        $collection1->getSelect()->group('main_table.Code');
        $collection2->addFieldToSelect('Code');
        $collection2->getSelect()->group('main_table.Code');
        $query1 = $collection1->getSelect()->__toString();
        $query2 = $collection2->getSelect()->__toString();
        if ($collection1->getSize() > 0) {
            foreach ($collection1 as $attribute) {
                $attributes['soft'][] = $attribute->getCode();
            }
        }
        if ($collection2->getSize() > 0) {
            foreach ($collection2 as $attribute) {
                $attributes['hard'][] = $attribute->getCode();
            }
        }
        return $attributes;
    }

    /**
     * Creating new attribute set, group and getting its id
     *
     * @param $itemCategoryCode
     * @param array $attributes
     * @return int|null
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws LocalizedException
     */
    public function createAttributeSetAndGroupsAndReturnAttributeSetId($itemCategoryCode, array $attributes)
    {
        $entityTypeCode = Product::ENTITY;
        $entityType     = $this->eavTypeFactory->create()->loadByCode($entityTypeCode);
        $defaultSetId   = $entityType->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $data         = [
            'attribute_set_name' => $itemCategoryCode,
            'entity_type_id'     => $entityType->getId(),
            'sort_order'         => 200,
        ];
        $attributeSet->setData($data);
        $attributeSet   = $this->attributeSetManagement->create($entityTypeCode, $attributeSet, $defaultSetId);
        $attributeGroup = $this->attributeSetGroupFactory->create();
        $attributeGroup->setAttributeSetId($attributeSet->getAttributeSetId());
        $attributeGroup->setAttributeGroupName(LSR::SC_REPLICATION_ATTRIBUTE_SET_SOFT_ATTRIBUTES_GROUP);
        $softAttributesGroup = $this->attributeGroupRepository->save($attributeGroup);
        $attributeGroup      = $this->attributeSetGroupFactory->create();
        $attributeGroup->setAttributeSetId($attributeSet->getAttributeSetId());
        $attributeGroup->setAttributeGroupName(LSR::SC_REPLICATION_ATTRIBUTE_SET_VARIANTS_ATTRIBUTES_GROUP);
        $hardAttributesGroup = $this->attributeGroupRepository->save($attributeGroup);

        foreach ($attributes as $type => $types) {
            foreach ($types as $attribute) {
                $formattedCode = $this->formatAttributeCode($attribute);

                $attribute = $this->eavConfig->getAttribute('catalog_product', $formattedCode);

                if (!$attribute->getId()) {
                    continue;
                }

                if ($type == 'soft') {
                    $this->attributeManagement->assign(
                        Product::ENTITY,
                        $attributeSet->getId(),
                        $softAttributesGroup->getAttributeGroupId(),
                        $formattedCode,
                        $attributeSet->getCollection()->count() * 10
                    );
                } else {
                    $this->attributeManagement->assign(
                        Product::ENTITY,
                        $attributeSet->getId(),
                        $hardAttributesGroup->getAttributeGroupId(),
                        $formattedCode,
                        $attributeSet->getCollection()->count() * 10
                    );
                }
            }
        }
        return $attributeSet->getAttributeSetId();
    }

    /**
     * Getting attribute set id given name
     *
     * @param $name
     * @return int|null
     */
    public function getAttributeSetByName($name)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'attribute_set_name',
            $name,
            'eq'
        )->setPageSize(1)->setCurrentPage(1);
        $result         = $this->attributeSetRepository->getList($searchCriteria->create());
        if ($result->getTotalCount()) {
            $items = $result->getItems();
            return reset($items)->getAttributeSetId();
        }
        return null;
    }

    /**
     * Setting product attributes in the product model
     *
     * @param $navId
     * @param $storeId
     * @param $productRepository
     * @param null $uomCodes
     * @throws LocalizedException
     */
    public function getProductAttributes(
        $navId,
        $storeId,
        $productRepository,
        $uomCodes = null
    ) {
        $criteria = $this->buildCriteriaForProductAttributes(
            $navId,
            -1,
            true,
            $storeId
        );
        /** @var ReplAttributeValueSearchResults $items */
        $items = $this->replAttributeValueRepositoryInterface->getList($criteria);
        /** @var ReplAttributeValue $item */
        foreach ($items->getItems() as $item) {
            $itemId        = $item->getLinkField1();
            $variantId     = $item->getLinkField2();
            $product       = $this->getProductDataByIdentificationAttributes($itemId, $variantId, '', 'global');
            $formattedCode = $this->formatAttributeCode($item->getCode());
            $attribute     = $this->eavConfig->getAttribute('catalog_product', $formattedCode);

            if (!$attribute->getId()) {
                continue;
            }

            if ($attribute->getFrontendInput() == 'multiselect') {
                $value = $this->getAllValuesForGivenMultiSelectAttribute(
                    $itemId,
                    $variantId,
                    $item->getCode(),
                    $formattedCode,
                    $storeId
                );
            } elseif ($attribute->getFrontendInput() == 'boolean') {
                if (strtolower($item->getValue()) == 'yes') {
                    $value = 1;
                } else {
                    $value = 0;
                }
            } else {
                $value = $item->getValue();
            }
            if (isset($formattedCode)) {
                $product->setData($formattedCode, $value);
                $product->getResource()->saveAttribute($product, $formattedCode);
                $this->processUomAttributes(
                    $uomCodes,
                    $itemId,
                    $formattedCode,
                    $value,
                    $variantId,
                    $productRepository
                );
            } else {
                $item->setData('is_failed', 1);
            }
            $item->setData('processed_at', $this->getDateTime());
            $item->setData('processed', 1);
            $item->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->replAttributeValueRepositoryInterface->save($item);
        }
    }

    /**
     * Getting attribute option id given value
     *
     * @param string $code
     * @param string $value
     * @return null|string
     * @throws LocalizedException
     */
    public function _getOptionIDByCode($code, $value)
    {
        if (!$value) {
            return null;
        }
        $defaultAttribute = $this->eavAttributeFactory->create();

        $attribute = $defaultAttribute->loadByCode(
            Product::ENTITY,
            $code
        )->setData('store_id', 0);

        foreach ($attribute->getSource()->getAllOptions() as $option) {
            if ($this->mbStrcasecmp($option['label'], $value) == 0) {
                return $option['value'];
            }
        }

        return null;
    }

    /**
     * Multibyte support strcasecmp function version.
     *
     * @param string $str1
     * @param string $str2
     * @return int
     */
    public function mbStrcasecmp($str1, $str2)
    {
        $encoding = mb_internal_encoding();
        return strcmp(
            mb_strtoupper($str1, $encoding),
            mb_strtoupper($str2, $encoding)
        );
    }

    /**
     * Get related variant attached to the parent product
     *
     * @param $parentProduct
     * @param $variant
     * @param $storeId
     * @return array
     */
    public function getRelatedVariantGivenConfAttributesValues($parentProduct, $variant, $storeId, $variantRemoval = false)
    {
        $configurableAttributesFinal = $this->getAllConfigurableAttributesGivenProduct(
            $parentProduct,
            $variant,
            $storeId,
            $variantRemoval
        );
        $availableUnitOfMeasures     = $this->getUomCodes($parentProduct->getSku(), $storeId);
        $simpleProducts              = [];

        if (count($availableUnitOfMeasures[$parentProduct->getSku()]) > 1) {
            foreach ($availableUnitOfMeasures[$parentProduct->getSku()] as $uom => $code) {
                $configurableAttributes   = $configurableAttributesFinal;
                $configurableAttributes[] = ['code' => LSR::LS_UOM_ATTRIBUTE, 'value' => $uom];
                $resultant                = $this->getConfAssoProduct($parentProduct, $configurableAttributes);
                if ($resultant) {
                    $simpleProducts[] = $resultant;
                }
            }
        } else {
            $resultant = $this->getConfAssoProduct($parentProduct, $configurableAttributesFinal);
            if ($resultant) {
                $simpleProducts[] = $resultant;
            }
        }

        return $simpleProducts;
    }

    /**
     * Get related configurable attributes attached to the parent product
     *
     * @param $parentProduct
     * @param $variant
     * @param $storeId
     * @return array
     */
    public function getAllConfigurableAttributesGivenProduct($parentProduct, $variant, $storeId, $variantRemoval = false)
    {
        $d1 = (($variant->getVariantDimension1()) ?: '');
        $d2 = (($variant->getVariantDimension2()) ?: '');
        $d3 = (($variant->getVariantDimension3()) ?: '');
        $d4 = (($variant->getVariantDimension4()) ?: '');
        $d5 = (($variant->getVariantDimension5()) ?: '');
        $d6 = (($variant->getVariantDimension6()) ?: '');

        $attributeCodes         = $this->_getAttributesCodes($parentProduct->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE), $storeId, $variantRemoval);
        $configurableAttributes = [];

        foreach ($attributeCodes as $keyCode => $valueCode) {
            if (isset($keyCode) && $keyCode != '') {
                $code                     = $valueCode;
                $codeValue                = ${'d' . $keyCode};
                $configurableAttributes[] = ['code' => $code, 'value' => $codeValue];
            }
        }

        return $configurableAttributes;
    }

    /**
     * Getting associated simple product id of the configurable product
     *
     * @param $product
     * @param $nameValueList
     * @return Product|null
     */
    public function getConfAssoProduct($product, $nameValueList)
    {
        //get configurable products attributes array with all values
        // with label (super attribute which use for configuration)
        $assPro = null;

        if ($product->getTypeId() != Configurable::TYPE_CODE) {
            // to bypass situation when simple products are not being properly converted into configurable.
            return $assPro;
        }
        $optionsData   = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        $superAttrList = $superAttrOptions = $attributeValues = [];

        // prepare array with attribute values
        foreach ($optionsData as $option) {
            $superAttrList[]                           = [
                'name' => $option['frontend_label'],
                'code' => $option['attribute_code'],
                'id'   => $option['attribute_id']
            ];
            $superAttrOptions[$option['attribute_id']] = $option['options'];

            foreach ($nameValueList as $nameValue) {
                if ($nameValue['code'] == $option['attribute_code']) {
                    foreach ($option['options'] as $attrOpt) {
                        if ($nameValue['value'] == $attrOpt['label']) {
                            $attributeValues[$option['attribute_id']] = $attrOpt['value'];
                        }
                    }
                }
            }
        }

        if (count($attributeValues) == count($nameValueList)) {
            // pass this prepared array with $product
            $assPro = $this->configurableProTypeModel->getProductByAttributes($attributeValues, $product);
            // it return complete product according to attribute values which you pass
        }

        return $assPro;
    }

    /**
     * Getting all configurable attribute codes
     *
     * @param $itemId
     * @param $storeId
     * @param $variantRemoval
     * @return array
     */
    public function _getAttributesCodes($itemId, $storeId, $variantRemoval = false)
    {
        $finalCodes = [];
        $isDeleted  = ($variantRemoval) ? [0,1]:0; //Filter isDeleted with 1 for variant removal
        $isDeletedCondition = ($variantRemoval) ? 'in':'eq';
        try {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('ItemId', $itemId)
                ->addFilter('isDeleted', $isDeleted, $isDeletedCondition)
                ->addFilter('Code', true, 'notnull')
                ->addFilter('Dimensions', true, 'notnull')
                ->addFilter('scope_id', $storeId, 'eq')->create();
            $sortOrder      = $this->sortOrder->setField('DimensionLogicalOrder')->setDirection(SortOrder::SORT_ASC);
            $searchCriteria->setSortOrders([$sortOrder]);
            $attributeCodes = $this->extendedVariantValueRepository->getList($searchCriteria)->getItems();

            /** @var ReplExtendedVariantValue $valueCode */
            foreach ($attributeCodes as $valueCode) {
                $formattedCode                           = $this->formatAttributeCode($valueCode->getCode());
                $finalCodes[$valueCode->getDimensions()] = $formattedCode;
                $valueCode->setData('processed_at', $this->getDateTime());
                $valueCode->setData('processed', 1);
                $valueCode->setData('is_updated', 0);
                // @codingStandardsIgnoreLine
                $this->extendedVariantValueRepository->save($valueCode);
            }
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return $finalCodes;
    }

    /**
     * Getting all available uom codes
     *
     * @param string $itemId
     * @param string $storeId
     * @return array
     */
    public function getUomCodes($itemId, $storeId)
    {
        $filters = [
            ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
        ];

        $itemUom          = [];
        $itemUom[$itemId] = [];
        $searchCriteria   = $this->buildCriteriaForDirect($filters, -1);

        /** @var ReplItemUnitOfMeasure $items */
        try {
            $items = $this->replItemUomRepository->getList($searchCriteria)->getItems();

            foreach ($items as $item) {
                $uomDescription = $this->getUomDescription($item);
                /** @var \Ls\Replication\Model\ReplItemUnitOfMeasure $item */
                $itemUom[$itemId][$uomDescription] = $item->getCode();
            }
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return $itemUom;
    }

    /**
     * Get Uom Description
     *
     * @param ReplItemUnitOfMeasureInterface $itemUom
     * @return string
     */
    public function getUomDescription($itemUom)
    {
        $uomDescription = null;

        if ($itemUom->getDescription()) {
            $uomDescription = $itemUom->getDescription();
        } else {
            $filters           = [
                ['field' => 'scope_id', 'value' => $itemUom->getScopeId(), 'condition_type' => 'eq'],
                ['field' => 'nav_id', 'value' => $itemUom->getCode(), 'condition_type' => 'eq']
            ];
            $searchCriteria    = $this->buildCriteriaForDirect($filters, -1);
            $replUnitOfMeasure = $this->replUnitOfMeasureRepository->getList($searchCriteria);

            if ($replUnitOfMeasure->getTotalCount()) {
                $uomDescription = current($replUnitOfMeasure->getItems())->getDescription();
            }
        }

        return $uomDescription;
    }

    /**
     * Get uom description given code and scope_id
     *
     * @param string $code
     * @param string $scopeId
     * @return string
     */
    public function getUomDescriptionGivenCodeAndScopeId($code, $scopeId)
    {
        $uomDescription    = '';
        $filters           = [
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq'],
            ['field' => 'nav_id', 'value' => $code, 'condition_type' => 'eq']
        ];
        $searchCriteria    = $this->buildCriteriaForDirect($filters, -1);
        $replUnitOfMeasure = $this->replUnitOfMeasureRepository->getList($searchCriteria);

        if ($replUnitOfMeasure->getTotalCount()) {
            $uomDescription = current($replUnitOfMeasure->getItems())->getDescription();
        }

        return $uomDescription;
    }

    /**
     * Get all available replicated values for given multiSelect attribute
     *
     * @param $itemId
     * @param $variantId
     * @param $attributeCode
     * @param $formattedCode
     * @param $storeId
     * @return string
     * @throws LocalizedException
     */
    public function getAllValuesForGivenMultiSelectAttribute(
        $itemId,
        $variantId,
        $attributeCode,
        $formattedCode,
        $storeId
    ) {
        $values  = [];
        $filters = [
            ['field' => 'Code', 'value' => $attributeCode, 'condition_type' => 'eq'],
            ['field' => 'LinkField1', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq']
        ];
        if (!empty($variantId)) {
            $filters [] = ['field' => 'LinkField2', 'value' => $variantId, 'condition_type' => 'eq'];
        }
        $criteria            = $this->buildCriteriaForDirect($filters, -1);
        $replAttributeValues = $this->replAttributeValueRepositoryInterface->
        getList($criteria)->getItems();

        foreach ($replAttributeValues as $replValue) {
            $value = $this->_getOptionIDByCode(
                $formattedCode,
                $replValue->getValue()
            );

            if ($value) {
                if (!in_array($value, $values)) {
                    $values[] = $value;
                }
            }
        }

        return implode(',', $values);
    }

    /**
     * Process Uom Attributes
     *
     * @param $uomCodes
     * @param $itemId
     * @param $formattedCode
     * @param $value
     * @param $variantId
     * @param $productRepository
     */
    public function processUomAttributes(
        $uomCodes,
        $itemId,
        $formattedCode,
        $value,
        $variantId,
        $productRepository
    ) {
        if (!empty($uomCodes)) {
            if (count($uomCodes[$itemId]) > 1) {
                $baseUnitOfMeasure = $uomCodes[$itemId . '-' . 'BaseUnitOfMeasure'];
                foreach ($uomCodes[$itemId] as $uomCode) {
                    if ($baseUnitOfMeasure != $uomCode && !empty($variantId)) {
                        $product = $this->getProductDataByIdentificationAttributes(
                            $itemId,
                            $variantId,
                            $uomCode,
                            0
                        );
                        $product->setData($formattedCode, $value);
                        $product->getResource()->saveAttribute($product, $formattedCode);
                    }
                }
            }
        }
    }

    /**
     * Get tax_class given name, create if not exists
     *
     * @param $name
     * @return int|string|null
     * @throws InputException
     * @throws LocalizedException
     */
    public function getTaxClassGivenName($name)
    {
        $criteriaBuilder = $this->searchCriteriaBuilder;
        $criteriaBuilder->addFilter('class_name', $name, 'eq')
            ->addFilter('class_type', ClassModel::TAX_CLASS_TYPE_PRODUCT, 'eq');
        $criteria = $criteriaBuilder->create();

        $taxClassList = $this->taxClassRepository->getList($criteria);

        if ($taxClassList->getTotalCount()) {
            $taxClass = current($taxClassList->getItems());
        } else {
            $taxClass = $this->classModelFactory->create();
            $taxClass->setClassName($name)
                ->setClassType(ClassModel::TAX_CLASS_TYPE_PRODUCT);
            $taxClassId = $this->taxClassRepository->save($taxClass);
            $taxClass   = $this->taxClassRepository->get($taxClassId);
        }

        return $taxClass;
    }

    /**
     * @param $itemId
     * @param $storeId
     * @param $scopeId
     * @param $variantId
     * @return false|ReplInvStatusRepository
     */
    public function getInventoryStatus($itemId, $storeId, $scopeId, $variantId = null)
    {
        $filters = [
            ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'StoreId', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq']
        ];

        if (isset($variantId)) {
            $filters[] = ['field' => 'VariantId', 'value' => $variantId, 'condition_type' => 'eq'];
        } else {
            $filters[] = ['field' => 'VariantId', 'value' => true, 'condition_type' => 'null'];
        }
        $searchCriteria = $this->buildCriteriaForDirect($filters, 1);
        /** @var ReplInvStatusRepository $inventoryStatus */
        $inventoryStatus = $this->replInvStatusRepository->getList($searchCriteria)->getItems();

        if (!empty($inventoryStatus)) {
            try {
                $inventoryStatus = reset($inventoryStatus);
            } catch (Exception $e) {
                $this->_logger->debug($e->getMessage());
                $inventoryStatus->setData('is_failed', 1);
            }
            $inventoryStatus->addData(['is_updated' => 0, 'processed_at' => $this->getDateTime(), 'processed' => 1]);
            $this->replInvStatusRepository->save($inventoryStatus);
        }

        return $inventoryStatus;
    }

    /**
     * Getting the inventory type from items table
     *
     * @param $itemId
     * @param $scopeId
     * @return \Ls\Replication\Model\ItemType|null
     */
    public function getInventoryType($itemId, $scopeId)
    {
        $filters = [
            ['field' => 'nav_id', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq']
        ];

        $searchCriteria = $this->buildCriteriaForDirect($filters, 1);
        /** @var ReplItem $item */
        $item = $this->itemRepository->getList($searchCriteria)->getItems();

        if (!empty($itemRepository)) {
            return $item->getType();

        }

        return null;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Update inventory and status
     *
     *
     * @param $sku
     * @param $replInvStatus
     * @return void
     */
    public function updateInventory($sku, $replInvStatus)
    {
        try {
            $parentProductsSkus = $this->getParentSkusOfChildrenSkus->execute([$sku]);
            $sourceItems        = [];
            $skus               = [$sku];
            foreach ($parentProductsSkus as $parentSku) {
                $productId = $this->getParentSkusOfChildrenSkus->getProductIdBySkus($parentSku);
                $parentSku = array_shift($parentSku);
                $this->setStockStatusChangedAuto($productId);
                $skus[]        = $parentSku;
                $sourceItems[] = $this->getSourceItemGivenData(
                    $parentSku,
                    0,
                    ($replInvStatus->getQuantity() > 0) ? 1 : 0
                );
            }
            $sourceItems[] = $this->getSourceItemGivenData(
                $sku,
                $replInvStatus->getQuantity(),
                ($replInvStatus->getQuantity() > 0) ? 1 : 0
            );
            $this->sourceItemsSave->execute($sourceItems);
            $this->parentItemProcessor->process($this->productRepository->get($sku));
            $productIds = array_values($this->getProductIdsBySkus->execute($skus));

            /**
             * Deleting relevant records from cataloginventory_stock_status
             * in order to get the correct values on next reindex
             */
            foreach ($productIds as $id) {
                $this->stockStatusRepository->deleteById($id);
            }
        } catch (Exception $e) {
            $this->_logger->debug(sprintf('Problem with sku: %s in method %s', $sku, __METHOD__));
            $this->_logger->debug($e->getMessage());
        }
    }

    /**
     * Set stock_status_changed_auto = 1 for configurable product in table cataloginventory_stock_item
     *
     * @param $productId
     * @return void
     */
    public function setStockStatusChangedAuto($productId)
    {
        $criteria = $this->criteriaInterfaceFactory->create();
        $criteria->setScopeFilter($this->stockConfiguration->getDefaultScopeId());

        $criteria->setProductsFilter($productId);
        $stockItemCollection = $this->stockItemRepository->getList($criteria);
        $allItems            = $stockItemCollection->getItems();
        if (empty($allItems)) {
            return;
        }
        $parentStockItem = array_shift($allItems);
        $parentStockItem
            ->setStockStatusChangedAuto(1)
            ->setStockStatusChangedAutomaticallyFlag(1);

        if (!$this->isSingleSourceMode->execute()) {
            $parentStockItem->setIsInStock(1);
        }

        $this->stockItemRepository->save($parentStockItem);
    }

    /**
     * Get source item given data
     *
     * @param $sku
     * @param $inventory
     * @param $status
     * @return mixed
     * @throws LocalizedException
     */
    public function getSourceItemGivenData($sku, $inventory, $status)
    {
        $defaultSourceCode = $this->defaultSourceProviderFactory->create()->getCode();
        $websiteId         = $this->storeManager->getStore()->getWebsiteId();
        $sourceCode        = $this->getSourceCodeFromWebsiteCode($defaultSourceCode, $websiteId);
        if ($sourceCode != $defaultSourceCode) {
            $this->deleteSourceItemsBySku($defaultSourceCode, $sku);
        }
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode($sourceCode);
        $sourceItem->setSku($sku);
        $sourceItem->setQuantity($inventory);
        $sourceItem->setStatus($status);

        return $sourceItem;
    }

    /**
     * Delete source items by sku
     *
     * @param $sourceCode
     * @param $sku
     * @return void
     * @throws InputException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function deleteSourceItemsBySku($sourceCode, $sku)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();
        $sourceItems    = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        if (!empty($sourceItems)) {
            $this->sourceItemDeleteRepository->execute($sourceItems);
        }
    }

    /**
     * Get source code from website
     *
     * @param $sourceCode
     * @param $websiteId
     * @return mixed|string|null
     * @throws LocalizedException
     */
    public function getSourceCodeFromWebsiteCode($sourceCode, $websiteId)
    {
        $websiteCode    = $this->storeManager->getWebsite($websiteId)->getCode();
        $stockId        = $this->getAssignedStockIdForWebsite->execute($websiteCode);
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->create();
        foreach ($this->getStockSourceLinks->execute($searchCriteria)->getItems() as $link) {
            $sourceCode = $link->getSourceCode();
        }

        return $sourceCode;
    }

    /**
     * Get all products in a store
     *
     * @param $websiteId
     * @return Collection
     */
    public function getProductCollectionGivenWebsiteId($websiteId)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addStoreFilter($websiteId);

        return $collection;
    }

    /**
     * @param $childCollection
     * @param $columns
     * @return mixed
     */
    public function getGivenColumnsFromGivenCollection($childCollection, $columns)
    {
        $parentCollection = clone $childCollection;
        $parentCollection
            ->getSelect()
            ->reset()
            ->from(['e' => new \Zend_Db_Expr('(' . $childCollection->getSelect() . ')')]);
        $parentCollection
            ->getSelect()
            ->reset(Select::COLUMNS)
            ->columns($columns);

        return $parentCollection;
    }

    /**
     * Get Connection
     *
     * @return AdapterInterface
     */
    public function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        }

        return $this->connection;
    }

    /**
     * Delete given table data given scope_id
     *
     * @param $tableName
     * @param $conditions
     * @return void
     */
    public function deleteGivenTableDataGivenConditions($tableName, $conditions)
    {
        try {
            $this->getConnection()->delete($tableName, $conditions);
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
    }

    /**
     * Update given table data given conditions
     *
     * @param $tableName
     * @param $bind
     * @param $conditions
     * @return void
     */
    public function updateGivenTableDataGivenConditions($tableName, $bind, $conditions)
    {
        try {
            $this->getConnection()->update(
                $tableName,
                $bind,
                $conditions
            );
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
    }

    /**
     * Get given table name
     *
     * @param $tableName
     * @return string
     */
    public function getGivenTableName($tableName)
    {
        return $this->resource->getTableName($tableName);
    }

    /**
     * Truncate given table
     *
     * @param $tableName
     * @return void
     */
    public function truncateGivenTable($tableName)
    {
        try {
            if ($this->getConnection()->isTableExists($tableName)) {
                $this->getConnection()->truncateTable($tableName);
            }
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
    }

    /**
     * Execute Given Query
     *
     * @param $query
     * @return void
     */
    public function executeGivenQuery($query)
    {
        try {
            $this->getConnection()->query($query);
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
    }

    /**
     * Get website id given store id
     *
     * @param $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteIdGivenStoreId($storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * Update all given crons with given status
     *
     * @param $crons
     * @param $scopeId
     * @param $status
     * @return void
     */
    public function updateAllGivenCronsWithGivenStatus($crons, $scopeId, $status)
    {
        foreach ($crons as $cron) {
            if (!empty($scopeId)) {
                $this->updateConfigValue(
                    $status,
                    $cron,
                    $scopeId
                );
            } else {
                $this->updateCronStatusForAllStores(
                    $status,
                    $cron
                );
            }
        }
    }

    /**
     * Get category given id
     *
     * @param $categoryId
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    public function getCategoryGivenId($categoryId)
    {
        return $this->categoryRepository->get($categoryId);
    }

    /**
     * Delete category children given category
     *
     * @param $category
     * @return void
     */
    public function deleteChildrenGivenCategory($category)
    {
        $this->categoryResourceModel->deleteChildren($category);
    }

    /**
     * Get all catalog rules in a website
     *
     * @param $websiteId
     * @return \Magento\CatalogRule\Model\ResourceModel\Rule\Collection
     */
    public function getCatalogRulesCollectionGivenWebsiteId($websiteId)
    {
        $websiteIds = [$websiteId];
        $collection = $this->ruleCollectionFactory->create();
        $collection->addFieldToFilter('website_ids', $websiteIds);

        return $collection;
    }

    /**
     * Get product attribute given code and scope
     *
     * @param string $formattedCode
     * @param mixed $scopeId
     * @return Attribute
     * @throws LocalizedException
     */
    public function getProductAttributeGivenCodeAndScope($formattedCode, $scopeId = 0)
    {
        $defaultAttribute = $this->eavAttributeFactory->create();

        return $defaultAttribute->loadByCode(
            Product::ENTITY,
            $formattedCode
        )->setData('store_id', $scopeId);
    }

    /**
     * Get product data by item id
     *
     * @param string $itemId
     * @param string $variantId
     * @param string $uom
     * @param string $storeId
     * @return mixed|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProductDataByIdentificationAttributes($itemId, $variantId = '', $uom = '', $storeId = '', $discardUom = false, $returnArray = false)
    {
        $currentStoreId = $this->storeManager->getStore()->getId();
        $searchCriteria = clone $this->searchCriteriaBuilder;

        if (!empty($itemId) || $itemId == '0') {
            $searchCriteria->addFilter(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $itemId);
        } else {
            return null;
        }

        if ($variantId != '') {
            $searchCriteria->addFilter(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE, $variantId);
        } else {
            $searchCriteria->addFilter(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE, true, 'null');
        }

        if ($uom != '') {
            $scopeId        = $this->storeManager->getStore()->getWebsiteId();
            $uomDescription = $this->getUomDescriptionGivenCodeAndScopeId($uom, $scopeId);
            $optionId       = $this->_getOptionIDByCode(
                LSR::LS_UOM_ATTRIBUTE,
                $uomDescription
            );

            if (isset($optionId)) {
                $searchCriteria->addFilter(LSR::LS_UOM_ATTRIBUTE, $optionId);
            }
        } else if (!$discardUom) {
            $searchCriteria->addFilter(LSR::LS_UOM_ATTRIBUTE, true, 'null');
        }

        if ($storeId !== '' && $storeId !== 'global') {
            $searchCriteria = $searchCriteria->addFilter(
                'store_id',
                $storeId
            )->create();
        } elseif ($storeId === 'global') {
            //add no store filter to fetch item id present in any store view
            $searchCriteria = $searchCriteria->create();
        } else {
            $searchCriteria = $searchCriteria->addFilter(
                'store_id',
                $this->storeManager->getStore()->getId()
            )->create();
        }

        if ($storeId === 'global') {
            $this->lsr->setStoreId(0);
        }
        $productList = $this->productRepository->getList($searchCriteria)->getItems();
        if ($storeId === 'global') {
            $this->lsr->setStoreId($currentStoreId);
        }
        if (!empty($productList) && !$returnArray) {
            return array_pop($productList);
        } elseif (!empty($productList) && $returnArray) {
            return $productList;
        } else {
            throw new NoSuchEntityException();
        }
    }

    /**
     * To get comma seperated visual swatch type attributes
     *
     * @param $storeId
     * @return array|string
     */
    public function getVisualSwatchAttributes($storeId)
    {
        return $this->lsr->getStoreConfig(LSR::VISUAL_TYPE_ATTRIBUTES, $storeId);
    }

    /**
     * Check if convert to visual swatch attribute type is enabled.
     *
     * @param $storeId
     * @return array|string
     */
    public function isVisualSwatchAttributes($storeId)
    {
        return $this->lsr->getStoreConfig(LSR::CONVERT_ATTRIBUTE_TO_VISUAL_SWATCH, $storeId);
    }

    /**
     * item manage stock
     *
     * @param $product
     * @param $type
     * @return mixed
     */
    public function manageStock($product, $type)
    {
        $useManageStock = 1;

        if (!empty($type) && $type != ItemType::INVENTORY) {
            $useManageStock = 0;
        }

        $product->setStockData([
            'use_config_manage_stock' => $useManageStock
        ]);

        return $product;
    }
}
