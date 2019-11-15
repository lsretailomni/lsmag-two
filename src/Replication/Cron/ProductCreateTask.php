<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ImageSize;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\StockHelper;
use \Ls\Replication\Api\ReplAttributeValueRepositoryInterface;
use \Ls\Replication\Api\ReplBarcodeRepositoryInterface as ReplBarcodeRepository;
use \Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use \Ls\Replication\Api\ReplHierarchyLeafRepositoryInterface as ReplHierarchyLeafRepository;
use \Ls\Replication\Api\ReplImageLinkRepositoryInterface;
use \Ls\Replication\Api\ReplImageRepositoryInterface as ReplImageRepository;
use \Ls\Replication\Api\ReplInvStatusRepositoryInterface as ReplInvStatusRepository;
use \Ls\Replication\Api\ReplItemRepositoryInterface as ReplItemRepository;
use \Ls\Replication\Api\ReplItemVariantRegistrationRepositoryInterface as ReplItemVariantRegistrationRepository;
use \Ls\Replication\Api\ReplPriceRepositoryInterface as ReplPriceRepository;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Model\ReplAttributeValue;
use \Ls\Replication\Model\ReplAttributeValueSearchResults;
use \Ls\Replication\Model\ReplBarcode;
use \Ls\Replication\Model\ReplBarcodeSearchResults;
use \Ls\Replication\Model\ReplExtendedVariantValue;
use \Ls\Replication\Model\ReplHierarchyLeafSearchResults;
use \Ls\Replication\Model\ReplImageLink;
use \Ls\Replication\Model\ReplImageLinkSearchResults;
use \Ls\Replication\Model\ReplInvStatus;
use \Ls\Replication\Model\ReplItem;
use \Ls\Replication\Model\ReplItemSearchResults;
use \Ls\Replication\Model\ReplItemVariantRegistration;
use \Ls\Replication\Model\ReplPrice;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyLeaf\CollectionFactory as ReplHierarchyLeafCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplInvStatus\CollectionFactory as ReplInvStatusCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplPrice\CollectionFactory as ReplPriceCollectionFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute\Interceptor;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProTypeModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\ImageContent;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Psr\Log\LoggerInterface;

/**
 * Class ProductCreateTask
 * @package Ls\Replication\Cron
 */
class ProductCreateTask
{
    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_products';

    /** @var Factory */
    public $factory;

    /** @var Item */
    public $item;

    /** @var Config */
    public $eavConfig;

    /** @var Configurable */
    public $configurable;

    /** @var Attribute */
    public $attribute;

    /** @var ProductInterfaceFactory */
    public $productFactory;

    /** @var ProductRepositoryInterface */
    public $productRepository;

    /** @var CollectionFactory */
    public $categoryCollectionFactory;

    /** @var CategoryLinkManagementInterface */
    public $categoryLinkManagement;

    /** @var ReplItemRepository */
    public $itemRepository;

    /** @var ReplExtendedVariantValueRepository */
    public $extendedVariantValueRepository;

    /** @var ReplImageRepository */
    public $imageRepository;

    /** @var ReplBarcodeRepository */
    public $replBarcodeRepository;

    /** @var ReplImageLinkRepositoryInterface */
    public $replImageLinkRepositoryInterface;

    /** @var ReplHierarchyLeafRepository */
    public $replHierarchyLeafRepository;

    /** @var ReplPriceRepository */
    public $replPriceRepository;

    /** @var ReplInvStatusRepository */
    public $replInvStatusRepository;

    /** @var ProductAttributeMediaGalleryEntryInterface */
    public $attributeMediaGalleryEntry;

    /** @var ImageContentFactory */
    public $imageContent;

    /** @var SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    public $sortOrder;

    /** @var FilterBuilder */
    public $filterBuilder;

    /** @var FilterGroupBuilder */
    public $filterGroupBuilder;

    /** @var LoggerInterface */
    public $logger;

    /** @var LoyaltyHelper */
    public $loyaltyHelper;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var ReplAttributeValueRepositoryInterface */
    public $replAttributeValueRepositoryInterface;

    /** @var LSR */
    public $lsr;

    /**
     * @var bool
     */
    public $cronStatus = false;

    /** @var StockHelper */
    public $stockHelper;
    /**
     * @var ReplItemVariantRegistrationRepository
     */
    public $replItemVariantRegistrationRepository;

    /**
     * @var ConfigurableProTypeModel
     */
    public $configurableProTypeModel;

    /**
     * @var ReplInvStatusCollectionFactory
     */
    public $replInvStatusCollectionFactory;

    /**
     * @var ReplPriceCollectionFactory
     */
    public $replPriceCollectionFactory;

    /**
     * @var ReplHierarchyLeafCollectionFactory
     */
    public $replHierarchyLeafCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    public $productResourceModel;

    /**
     * @var StockRegistryInterface
     */
    public $stockRegistry;

    /**
     * ProductCreateTask constructor.
     * @param Factory $factory
     * @param Item $item
     * @param Config $eavConfig
     * @param ConfigurableProTypeModel $configurable
     * @param Attribute $attribute
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductAttributeMediaGalleryEntryInterface $attributeMediaGalleryEntry
     * @param ImageContentFactory $imageContent
     * @param CollectionFactory $categoryCollectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param ReplItemRepository $itemRepository
     * @param ReplItemVariantRegistrationRepository $replItemVariantRegistrationRepository
     * @param ReplExtendedVariantValueRepository $extendedVariantValueRepository
     * @param ReplImageRepository $replImageRepository
     * @param ReplHierarchyLeafRepository $replHierarchyLeafRepository
     * @param ReplBarcodeRepository $replBarcodeRepository
     * @param ReplPriceRepository $replPriceRepository
     * @param ReplInvStatusRepository $replInvStatusRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrder $sortOrder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface
     * @param LoyaltyHelper $loyaltyHelper
     * @param ReplicationHelper $replicationHelper
     * @param ReplAttributeValueRepositoryInterface $replAttributeValueRepositoryInterface
     * @param LoggerInterface $logger
     * @param LSR $LSR
     * @param ConfigurableProTypeModel $configurableProTypeModel
     * @param StockHelper $stockHelper
     * @param ReplInvStatusCollectionFactory $replInvStatusCollectionFactory
     * @param ReplPriceCollectionFactory $replPriceCollectionFactory
     * @param ReplHierarchyLeafCollectionFactory $replHierarchyLeafCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResourceModel
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        Factory $factory,
        Item $item,
        Config $eavConfig,
        Configurable $configurable,
        Attribute $attribute,
        ProductInterfaceFactory $productInterfaceFactory,
        ProductRepositoryInterface $productRepository,
        ProductAttributeMediaGalleryEntryInterface $attributeMediaGalleryEntry,
        ImageContentFactory $imageContent,
        CollectionFactory $categoryCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagement,
        ReplItemRepository $itemRepository,
        ReplItemVariantRegistrationRepository $replItemVariantRegistrationRepository,
        ReplExtendedVariantValueRepository $extendedVariantValueRepository,
        ReplImageRepository $replImageRepository,
        ReplHierarchyLeafRepository $replHierarchyLeafRepository,
        ReplBarcodeRepository $replBarcodeRepository,
        ReplPriceRepository $replPriceRepository,
        ReplInvStatusRepository $replInvStatusRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrder $sortOrder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface,
        LoyaltyHelper $loyaltyHelper,
        ReplicationHelper $replicationHelper,
        ReplAttributeValueRepositoryInterface $replAttributeValueRepositoryInterface,
        LoggerInterface $logger,
        LSR $LSR,
        ConfigurableProTypeModel $configurableProTypeModel,
        StockHelper $stockHelper,
        ReplInvStatusCollectionFactory $replInvStatusCollectionFactory,
        ReplPriceCollectionFactory $replPriceCollectionFactory,
        ReplHierarchyLeafCollectionFactory $replHierarchyLeafCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResourceModel,
        StockRegistryInterface $stockRegistry
    ) {
        $this->factory                               = $factory;
        $this->item                                  = $item;
        $this->eavConfig                             = $eavConfig;
        $this->configurable                          = $configurable;
        $this->attribute                             = $attribute;
        $this->productFactory                        = $productInterfaceFactory;
        $this->productRepository                     = $productRepository;
        $this->attributeMediaGalleryEntry            = $attributeMediaGalleryEntry;
        $this->imageContent                          = $imageContent;
        $this->categoryCollectionFactory             = $categoryCollectionFactory;
        $this->categoryLinkManagement                = $categoryLinkManagement;
        $this->itemRepository                        = $itemRepository;
        $this->replItemVariantRegistrationRepository = $replItemVariantRegistrationRepository;
        $this->extendedVariantValueRepository        = $extendedVariantValueRepository;
        $this->imageRepository                       = $replImageRepository;
        $this->replHierarchyLeafRepository           = $replHierarchyLeafRepository;
        $this->replBarcodeRepository                 = $replBarcodeRepository;
        $this->replPriceRepository                   = $replPriceRepository;
        $this->replInvStatusRepository               = $replInvStatusRepository;
        $this->searchCriteriaBuilder                 = $searchCriteriaBuilder;
        $this->sortOrder                             = $sortOrder;
        $this->filterBuilder                         = $filterBuilder;
        $this->filterGroupBuilder                    = $filterGroupBuilder;
        $this->logger                                = $logger;
        $this->replImageLinkRepositoryInterface      = $replImageLinkRepositoryInterface;
        $this->loyaltyHelper                         = $loyaltyHelper;
        $this->replicationHelper                     = $replicationHelper;
        $this->replAttributeValueRepositoryInterface = $replAttributeValueRepositoryInterface;
        $this->lsr                                   = $LSR;
        $this->configurableProTypeModel              = $configurableProTypeModel;
        $this->stockHelper                           = $stockHelper;
        $this->replInvStatusCollectionFactory        = $replInvStatusCollectionFactory;
        $this->replPriceCollectionFactory            = $replPriceCollectionFactory;
        $this->replHierarchyLeafCollectionFactory    = $replHierarchyLeafCollectionFactory;
        $this->productResourceModel                  = $productResourceModel;
        $this->stockRegistry                         = $stockRegistry;
    }

    /**
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function execute()
    {
        $this->replicationHelper->updateConfigValue(date('d M,Y h:i:s A'), self::CONFIG_PATH_LAST_EXECUTE);
        $fullReplicationImageLinkStatus = $this->lsr->getStoreConfig(ReplEcommImageLinksTask::CONFIG_PATH_STATUS);
        $fullReplicationBarcodeStatus   = $this->lsr->getStoreConfig(ReplEcommBarcodesTask::CONFIG_PATH_STATUS);
        $fullReplicationPriceStatus     = $this->lsr->getStoreConfig(ReplEcommPricesTask::CONFIG_PATH_STATUS);
        $fullReplicationInvStatus       = $this->lsr->getStoreConfig(ReplEcommInventoryStatusTask::CONFIG_PATH_STATUS);
        $cronCategoryCheck              = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_CATEGORY);
        $cronAttributeCheck             = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE);
        $cronAttributeVariantCheck      = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT);
        if ($cronCategoryCheck == 1 &&
            $cronAttributeCheck == 1 &&
            $cronAttributeVariantCheck == 1 &&
            $fullReplicationImageLinkStatus == 1 &&
            $fullReplicationBarcodeStatus == 1 &&
            $fullReplicationPriceStatus == 1 &&
            $fullReplicationInvStatus == 1) {
            $this->logger->debug('Running ProductCreateTask ');
            $val1 = ini_get('max_execution_time');
            $val2 = ini_get('memory_limit');
            $this->logger->debug('ENV Variables Values before:' . $val1 . ' ' . $val2);
            // @codingStandardsIgnoreStart
            @ini_set('max_execution_time', 3600);
            @ini_set('memory_limit', -1);
            // @codingStandardsIgnoreEnd
            $val1 = ini_get('max_execution_time');
            $val2 = ini_get('memory_limit');
            $this->logger->debug('ENV Variables Values after:' . $val1 . ' ' . $val2);
            $storeId          = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
            $productBatchSize = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_PRODUCT_BATCHSIZE);
            /** @var SearchCriteria $criteria */
            $criteria = $this->replicationHelper->buildCriteriaForNewItems('', '', '', $productBatchSize);
            /** @var ReplItemSearchResults $items */
            $items = $this->itemRepository->getList($criteria);
            /** @var ReplItem $item */
            foreach ($items->getItems() as $item) {
                try {
                    $productData = $this->productRepository->get($item->getNavId());
                    try {
                        $productData->setName($item->getDescription());
                        $productData->setMetaTitle($item->getDescription());
                        $productData->setDescription($item->getDetails());
                        $productData->setWeight($item->getGrossWeight());
                        $productData->setCustomAttribute('uom', $item->getBaseUnitOfMeasure());
                        $productImages = $this->replicationHelper->getImageLinksByType($item->getNavId(), 'Item');
                        if ($productImages) {
                            $this->logger->debug('Found images for the item ' . $item->getNavId());
                            $productData->setMediaGalleryEntries($this->getMediaGalleryEntries($productImages));
                        }
                        $product = $this->getProductAttributes($productData, $item);
                        // @codingStandardsIgnoreStart
                        $this->productRepository->save($product);
                        $item->setData('is_updated', '0');
                        $item->setData('processed', '1');
                        $this->itemRepository->save($item);
                        // @codingStandardsIgnoreEnd
                    } catch (Exception $e) {
                        $this->logger->debug($e->getMessage());
                    }
                } catch (NoSuchEntityException $e) {
                    /** @var ProductInterface $product */
                    $product = $this->productFactory->create();
                    $product->setName($item->getDescription());
                    $product->setMetaTitle($item->getDescription());
                    $product->setSku($item->getNavId());
                    $product->setUrlKey($this->oSlug($item->getDescription() . '-' . $item->getNavId()));
                    $product->setVisibility(Visibility::VISIBILITY_BOTH);
                    $product->setWeight($item->getGrossWeight());
                    $product->setDescription($item->getDetails());
                    $itemPrice = $this->getItemPrice($item->getNavId());
                    if (isset($itemPrice)) {
                        $product->setPrice($itemPrice->getUnitPrice());
                    } else {
                        $product->setPrice($item->getUnitPrice());
                    }

                    $product->setAttributeSetId(4);
                    $product->setStatus(Status::STATUS_ENABLED);
                    $product->setTypeId(Type::TYPE_SIMPLE);
                    $product->setCustomAttribute('uom', $item->getBaseUnitOfMeasure());
                    /** @var ReplBarcodeRepository $itemBarcodes */
                    $itemBarcodes = $this->_getBarcode($item->getNavId());
                    if (isset($itemBarcodes[$item->getNavId()])) {
                        $product->setCustomAttribute('barcode', $itemBarcodes[$item->getNavId()]);
                    }
                    $itemStock = $this->getInventoryStatus($item->getNavId(), $storeId);
                    $product->setStockData([
                        'use_config_manage_stock' => 1,
                        'is_in_stock'             => ($itemStock > 0) ? 1 : 0,
                        'qty'                     => $itemStock
                    ]);
                    $productImages = $this->replicationHelper->getImageLinksByType($item->getNavId(), 'Item');
                    if ($productImages) {
                        $this->logger->debug('Found images for the item ' . $item->getNavId());
                        $product->setMediaGalleryEntries($this->getMediaGalleryEntries($productImages));
                    }
                    $this->logger->debug('trying to save product ' . $item->getNavId());
                    /** @var ProductRepositoryInterface $productSaved */
                    $product = $this->getProductAttributes($product, $item);
                    // @codingStandardsIgnoreStart
                    $productSaved = $this->productRepository->save($product);
                    $variants     = $this->getNewOrUpdatedProductVariants(-1, $item->getNavId());
                    if (!empty($variants)) {
                        $this->createConfigurableProducts($productSaved, $item, $itemBarcodes, $variants);
                    }
                    $item->setData('processed', '1');
                    $this->itemRepository->save($item);
                    // @codingStandardsIgnoreEnd
                }
            }
            if (count($items->getItems()) == 0) {
                $this->caterItemsRemoval();
                $this->assignProductToCategory();
                $this->cronStatus             = true;
                $fullReplicationVariantStatus = $this->lsr->getStoreConfig(
                    ReplEcommItemVariantRegistrationsTask::CONFIG_PATH_STATUS
                );
                if ($fullReplicationVariantStatus == 1) {
                    $this->updateVariantsOnly();
                    $this->caterVariantsRemoval();
                }
                // This will update all the latest images for the product including new
                $this->updateAndAddNewImageOnly();
                $this->updateBarcodeOnly();
                $this->updatePriceOnly($storeId);
                $this->updateInventoryOnly($storeId);
            }
            $this->logger->debug('End ProductCreateTask');
        } else {
            $this->logger->debug('Product Replication cron fails because custom category, 
            custom attribute or full image replication cron not executed successfully.');
        }
        $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_PRODUCT);
    }

    /**
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function executeManually()
    {
        $this->execute();
        $criteria           = $this->replicationHelper->buildCriteriaForNewItems('', '', '', -1);
        $items              = $this->itemRepository->getList($criteria);
        $itemsLeftToProcess = count($items->getItems());
        return [$itemsLeftToProcess];
    }

    /**
     * @param ProductInterface $product
     * @param ReplItem $replItem
     * @return ProductInterface
     * @throws LocalizedException
     */
    public function getProductAttributes(
        ProductInterface $product,
        ReplItem $replItem
    ) {
        $criteria = $this->replicationHelper->buildCriteriaForProductAttributes($replItem->getNavId(), 100);
        /** @var ReplAttributeValueSearchResults $items */
        $items = $this->replAttributeValueRepositoryInterface->getList($criteria);
        /** @var ReplAttributeValue $item */
        foreach ($items->getItems() as $item) {
            $formattedCode = $this->replicationHelper->formatAttributeCode($item->getCode());
            $attribute     = $this->eavConfig->getAttribute('catalog_product', $formattedCode);
            if ($attribute->getFrontendInput() == 'multiselect') {
                $value = $this->_getOptionIDByCode($formattedCode, $item->getValue());
            } elseif ($attribute->getFrontendInput() == 'boolean') {
                if (strtolower($item->getValue()) == 'yes') {
                    $value = 1;
                } else {
                    $value = 0;
                }
            } else {
                $value = $item->getValue();
            }
            $product->setData($formattedCode, $value);
            $item->setData('processed', '1');
            // @codingStandardsIgnoreStart
            $this->replAttributeValueRepositoryInterface->save($item);
            // @codingStandardsIgnoreEnd
        }
        return $product;
    }

    /**
     * @param $productImages
     * @return array
     * @throws Exception
     */
    private function getMediaGalleryEntries($productImages)
    {
        $galleryArray = [];
        /** @var ReplImageLink $image */
        foreach ($productImages as $i => $image) {
            $types     = [];
            $imageSize = [
                'height' => $this->lsr::DEFAULT_ITEM_IMAGE_HEIGHT,
                'width'  => $this->lsr::DEFAULT_ITEM_IMAGE_WIDTH
            ];
            /** @var ImageSize $imageSizeObject */
            $imageSizeObject = $this->loyaltyHelper->getImageSize($imageSize);
            $result          = $this->loyaltyHelper->getImageById($image->getImageId(), $imageSizeObject);
            if (!empty($result) && !empty($result["format"]) && !empty($result["image"])) {
                /** @var ImageContent $imageContent */
                $imageContent = $this->imageContent->create()
                    ->setBase64EncodedData($result["image"])
                    ->setName($this->oSlug($image->getImageId()))
                    ->setType($this->getMimeType($result["image"]));
                $this->attributeMediaGalleryEntry->setMediaType('image')
                    ->setLabel(($image->getDescription()) ? $image->getDescription() : 'Product Image')
                    ->setPosition($image->getDisplayOrder())
                    ->setDisabled(false)
                    ->setContent($imageContent);
                if ($i == 0) {
                    $types = ['image', 'small_image', 'thumbnail'];
                }
                $this->attributeMediaGalleryEntry->setTypes($types);
                $galleryArray[] = clone $this->attributeMediaGalleryEntry;
                $image->setData('processed', '1');
                $image->setData('is_updated', '0');
                // @codingStandardsIgnoreStart
                $this->replImageLinkRepositoryInterface->save($image);
                // @codingStandardsIgnoreEnd
            }
        }
        return $galleryArray;
    }

    /**
     * @param $productGroupId
     * @return mixed
     * @throws LocalizedException
     */
    private function findCategoryIdFromFactory($productGroupId)
    {
        $categoryCollection = $this->categoryCollectionFactory->create()->addAttributeToFilter(
            'nav_id',
            $productGroupId
        )->setPageSize(1);
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
     * Assign products to category using HierarchyCode
     */
    private function assignProductToCategory()
    {
        $categoriesArray     = [];
        $previousCategoryIds = [];
        $hierarchyCollection = [];
        $hierarchyCode       = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE);
        if (empty($hierarchyCode)) {
            $this->logger->debug('Hierarchy Code not defined in the configuration.');
            return;
        }
        $filters  = [
            ['field' => 'main_table.NodeId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'main_table.HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, 100);
        /** @var ReplHierarchyLeafSearchResults $replHierarchyLeafRepository */
        $collection = $this->replHierarchyLeafCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'nav_id',
            'ls_replication_repl_item',
            'nav_id'
        );

        try {
            foreach ($collection as $hierarchyLeaf) {
                try {
                    $product             = $this->productRepository->get($hierarchyLeaf->getNavId());
                    $previousCategoryIds = $product->getCategoryIds();
                } catch (Exception $e) {
                    $this->logger->debug($e->getMessage());
                    $hierarchyLeaf->setData('processed', '1');
                    $hierarchyLeaf->setData('is_updated', '0');
                    $this->replHierarchyLeafRepository->save($hierarchyLeaf);
                    continue;
                }

                $currentCategoryIds = $this->findCategoryIdFromFactory($hierarchyLeaf->getNodeId());

                if (array_key_exists($hierarchyLeaf->getNavId(), $categoriesArray)) {
                    $categoriesArray[$hierarchyLeaf->getNavId()] =
                        array_unique(
                            array_merge(
                                $currentCategoryIds,
                                $categoriesArray[$hierarchyLeaf->getNavId()]
                            )
                        );
                } else {
                    $categoriesArray[$hierarchyLeaf->getNavId()] =
                        array_unique(
                            array_merge(
                                $currentCategoryIds,
                                $previousCategoryIds
                            )
                        );
                }
                $hierarchyCollection[$hierarchyLeaf->getNavId()][] = $hierarchyLeaf;
            }

            foreach ($categoriesArray as $catKey => $catArray) {
                if (!empty($catArray)) {
                    $this->categoryLinkManagement->assignProductToCategories(
                        $catKey,
                        $catArray
                    );
                    foreach ($hierarchyCollection[$catKey] as $leaf) {
                        $leaf->setData('processed', '1');
                        $leaf->setData('is_updated', '0');
                        $this->replHierarchyLeafRepository->save($leaf);
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @param $image64
     * @return string
     */
    private function getMimeType($image64)
    {
        // @codingStandardsIgnoreStart
        return finfo_buffer(finfo_open(), base64_decode($image64), FILEINFO_MIME_TYPE);
        // @codingStandardsIgnoreEnd
    }

    /**
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
     * @param int $pagesize
     * @param null $itemId
     * @return mixed
     */
    private function getNewOrUpdatedProductVariants($pagesize = 100, $itemId = null)
    {
        $filters = [['field' => 'VariantId', 'value' => true, 'condition_type' => 'notnull']];
        if (isset($itemId)) {
            $filters[] = ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'];
        } else {
            $filters[] = ['field' => 'ItemId', 'value' => true, 'condition_type' => 'notnull'];
        }
        /** @var SearchCriteria $criteria */
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, $pagesize);
        $variants = $this->replItemVariantRegistrationRepository->getList($criteria)->getItems();
        return $variants;
    }

    /**
     * Return all updated variants only
     * @param type $filters
     * @return type
     */
    private function getDeletedItemsOnly($filters)
    {
        /** @var SearchCriteria $criteria */
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly($filters);
        $items    = $this->itemRepository->getList($criteria);
        return $items;
    }

    /**
     * Return all updated variants only
     * @param array $filters
     * @return type
     */
    private function getDeletedVariantsOnly($filters)
    {
        /** @var SearchCriteria $criteria */
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly($filters);
        $variants = $this->replItemVariantRegistrationRepository->getList($criteria)->getItems();
        return $variants;
    }

    /**
     * @param $code
     * @param $value
     * @return null|string
     * @throws LocalizedException
     */
    public function _getOptionIDByCode($code, $value)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $code);
        $optionID  = $attribute->getSource()->getOptionId($value);
        return $optionID;
    }

    /**
     * @param $itemId
     * @return array
     */
    public function _getAttributesCodes($itemId)
    {
        $finalCodes = [];
        try {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('ItemId', $itemId)->create();
            $sortOrder      = $this->sortOrder->setField('DimensionLogicalOrder')->setDirection(SortOrder::SORT_ASC);
            $searchCriteria->setSortOrders([$sortOrder]);
            $attributeCodes = $this->extendedVariantValueRepository->getList($searchCriteria)->getItems();
            /** @var ReplExtendedVariantValue $valueCode */
            foreach ($attributeCodes as $valueCode) {
                $formattedCode                           = $this->replicationHelper->formatAttributeCode($valueCode->getCode());
                $finalCodes[$valueCode->getDimensions()] = $formattedCode;
                $valueCode->setData('processed', '1');
                // @codingStandardsIgnoreStart
                $this->extendedVariantValueRepository->save($valueCode);
                // @codingStandardsIgnoreEnd
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        return $finalCodes;
    }

    /**
     * Return all the barcodes including the variant
     *
     * @param $itemId
     * @return array
     */
    public function _getBarcode($itemId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('ItemId', $itemId)->create();
        $allBarCodes    = [];
        /** @var ReplBarcodeRepository $itemBarcodes */
        $itemBarcodes = $this->replBarcodeRepository->getList($searchCriteria)->getItems();
        foreach ($itemBarcodes as $itemBarcode) {
            $sku               = $itemBarcode->getItemId() .
                (($itemBarcode->getVariantId()) ? '-' . $itemBarcode->getVariantId() : '');
            $allBarCodes[$sku] = $itemBarcode->getNavId();
        }
        return $allBarCodes;
    }

    /**
     * Return item
     *
     * @param $itemId
     * @return array
     */
    public function _getItem($itemId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('nav_id', $itemId)->create();
        $items          = [];
        /** @var ReplItemRepository $items */
        $items = $this->itemRepository->getList($searchCriteria)->getItems();
        foreach ($items as $item) {
            return $item;
        }
    }

    /**
     * @param $itemId
     * @param null $variantId
     * @return mixed
     */
    public function getItemPrice($itemId, $variantId = null)
    {
        $storeId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
        $filters = [
            ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'StoreId', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'QtyPerUnitOfMeasure', 'value' => 0, 'condition_type' => 'eq'],
        ];
        if ($variantId) {
            $filters[] = ['field' => 'VariantId', 'value' => $variantId, 'condition_type' => 'eq'];
        }
        $item           = null;
        $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, 1);
        /** @var ReplPriceRepository $items */
        try {
            $items = $this->replPriceRepository->getList($searchCriteria)->getItems();
            if (!empty($items)) {
                $item = reset($items);
                /** @var ReplInvStatus $invStatus */
                $item->setData('is_updated', '0');
                $item->setData('processed', '1');
                $this->replPriceRepository->save($item);
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        return $item;
    }

    /**
     * Update/Add the modified/added variants of the item
     */
    public function updateVariantsOnly()
    {
        $variants = $this->getNewOrUpdatedProductVariants();
        if (!empty($variants)) {
            try {
                foreach ($variants as $variant) {
                    $items[] = $variant->getItemId();
                }
                $items = array_unique($items);
                foreach ($items as $item) {
                    $productData = $this->productRepository->get($item);
                    /** @var ReplBarcodeRepository $itemBarcodes */
                    $itemBarcodes = $this->_getBarcode($item);
                    /** @var ReplItemRepository $itemData */
                    $itemData = $this->_getItem($item);
                    $this->createConfigurableProducts($productData, $itemData, $itemBarcodes, $variants);
                }
            } catch (Exception $e) {
                $this->logger->debug("Problem with sku: " . $item . " in " . __METHOD__);
                $this->logger->debug($e->getMessage());
                return;
            }
        }
    }

    /**
     * Cater Configurable Products Removal
     */
    public function caterItemsRemoval()
    {
        $filters = [
            ['field' => 'nav_id', 'value' => true, 'condition_type' => 'notnull']
        ];
        $items   = $this->getDeletedItemsOnly($filters);

        if (!empty($items->getItems())) {
            try {
                foreach ($items->getItems() as $value) {
                    $sku         = $value->getNavId();
                    $productData = $this->productRepository->get($sku);
                    $productData->setStatus(Status::STATUS_DISABLED);
                    // @codingStandardsIgnoreStart
                    $this->productRepository->save($productData);
                    $value->setData('is_updated', '0');
                    $value->setData('processed', '1');
                    $value->setData('IsDeleted', '0');
                    $this->itemRepository->save($value);
                    // @codingStandardsIgnoreEnd
                }
            } catch (Exception $e) {
                $this->logger->debug("Problem with sku: " . $sku . " in " . __METHOD__);
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Cater SimpleProducts Removal
     */
    public function caterVariantsRemoval()
    {
        $filters  = [
            ['field' => 'ItemId', 'value' => true, 'condition_type' => 'notnull']
        ];
        $variants = $this->getDeletedVariantsOnly($filters);

        if (!empty($variants)) {
            try {
                /** @var ReplItemVariantRegistration $value */
                foreach ($variants as $value) {
                    $d1                     = (($value->getVariantDimension1()) ? $value->getVariantDimension1() : '');
                    $d2                     = (($value->getVariantDimension2()) ? $value->getVariantDimension2() : '');
                    $d3                     = (($value->getVariantDimension3()) ? $value->getVariantDimension3() : '');
                    $d4                     = (($value->getVariantDimension4()) ? $value->getVariantDimension4() : '');
                    $d5                     = (($value->getVariantDimension5()) ? $value->getVariantDimension5() : '');
                    $d6                     = (($value->getVariantDimension6()) ? $value->getVariantDimension6() : '');
                    $itemId                 = $value->getItemId();
                    $productData            = $this->productRepository->get($itemId);
                    $attributeCodes         = $this->_getAttributesCodes($productData->getSku());
                    $configurableAttributes = [];
                    foreach ($attributeCodes as $keyCode => $valueCode) {
                        if (isset($keyCode) && $keyCode != '') {
                            $code                     = $valueCode;
                            $codeValue                = ${'d' . $keyCode};
                            $configurableAttributes[] = ["code" => $code, 'value' => $codeValue];
                        }
                    }
                    $associatedSimpleProduct = $this->getConfAssoProductId($productData, $configurableAttributes);
                    if ($associatedSimpleProduct != null) {
                        $associatedSimpleProduct->setStatus(
                            Status::STATUS_DISABLED
                        );
                        // @codingStandardsIgnoreStart
                        $this->productRepository->save($associatedSimpleProduct);
                        // @codingStandardsIgnoreEnd
                    }
                    $value->setData('is_updated', '0');
                    $value->setData('processed', '1');
                    $value->setData('IsDeleted', '0');
                    // @codingStandardsIgnoreStart
                    $this->replItemVariantRegistrationRepository->save($value);
                    // @codingStandardsIgnoreEnd
                }
            } catch (Exception $e) {
                $this->logger->debug("Problem with sku: " . $itemId . " in " . __METHOD__);
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * @param $product
     * @param $nameValueList
     * @return Product|null
     */
    public function getConfAssoProductId($product, $nameValueList)
    {
        //get configurable products attributes array with all values
        // with label (super attribute which use for configuration)
        $assPro = null;
        if ($product->getTypeId() != 'configurable') {
            // to bypass situation when simple products are not being properly converted into configurable.
            return $assPro;
        }
        $optionsData      = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        $superAttrList    = [];
        $superAttrOptions = [];
        $attributeValues  = [];

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
     * Update/Add the modified/added images of the item
     */
    public function updateAndAddNewImageOnly()
    {
        $filters  = [
            ['field' => 'TableName', 'value' => 'Item%', 'condition_type' => 'like'],
            ['field' => 'TableName', 'value' => 'Item Category', 'condition_type' => 'neq']

        ];
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, 2000);
        /** @var ReplImageLinkSearchResults $images */
        $images         = $this->replImageLinkRepositoryInterface->getList($criteria);
        $processedItems = [];
        if ($images->getTotalCount() > 0) {
            /** @var ReplImageLink $image */
            foreach ($images->getItems() as $image) {
                if (in_array($image->getKeyValue(), $processedItems)) {
                    continue;
                }
                try {
                    if ($image->getTableName() == "Item" || $image->getTableName() == "Item Variant") {
                        if ($image->getTableName() == "Item") {
                            $allImages = $this->replicationHelper->getImageLinksByType(
                                $image->getKeyValue(),
                                'Item'
                            );
                        } elseif ($image->getTableName() == "Item Variant") {
                            $allImages = $this->replicationHelper->getImageLinksByType(
                                $image->getKeyValue(),
                                'Item Variant'
                            );
                        }
                        $item = $image->getKeyValue();
                        $item = str_replace(',', '-', $item);
                        $image->setData('is_updated', '0');
                        $image->setData('processed', '1');
                        // @codingStandardsIgnoreStart
                        $this->replImageLinkRepositoryInterface->save($image);
                        /* @var ProductRepositoryInterface $productData */
                        $productData  = $this->productRepository->get($item);
                        $galleryImage = $allImages;
                        if ($galleryImage) {
                            $productData->setMediaGalleryEntries($this->getMediaGalleryEntries($galleryImage));
                            $this->productRepository->save($productData);
                        }

                        // @codingStandardsIgnoreEnd
                        // Adding items into an array whose images are processed.
                        $processedItems[] = $image->getKeyValue();
                    }
                } catch (Exception $e) {
                    $this->logger->debug("Problem with sku: " . $item . " in " . __METHOD__);
                    $this->logger->debug($e->getMessage());
                }
            }
        }
    }

    /**
     * Update the modified/added barcode of the items & item variants
     */
    public function updateBarcodeOnly()
    {
        $cronProductCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_PRODUCT);
        if ($cronProductCheck == 1) {
            $criteria = $this->replicationHelper->buildCriteriaForNewItems();
            /** @var ReplBarcodeSearchResults $replBarcodes */
            $replBarcodes = $this->replBarcodeRepository->getList($criteria);
            if ($replBarcodes->getTotalCount() > 0) {
                /** @var ReplBarcode $replBarcode */
                foreach ($replBarcodes->getItems() as $replBarcode) {
                    try {
                        if (!$replBarcode->getVariantId()) {
                            $sku = $replBarcode->getItemId();
                        } else {
                            $sku = $replBarcode->getItemId() . '-' . $replBarcode->getVariantId();
                        }
                        $productData = $this->productRepository->get($sku);
                        if (isset($productData)) {
                            $productData->setBarcode($replBarcode->getNavId());
                            // @codingStandardsIgnoreStart
                            $this->productResourceModel->saveAttribute($productData, 'barcode');
                            // @codingStandardsIgnoreEnd
                        }
                    } catch (Exception $e) {
                        $this->logger->debug("Problem with sku: " . $sku . " in " . __METHOD__);
                        $this->logger->debug($e->getMessage());
                    }
                    $replBarcode->setData('is_updated', '0');
                    $replBarcode->setData('processed', '1');
                    $this->replBarcodeRepository->save($replBarcode);
                }
            }
        }
    }

    /**
     * @param $storeId
     * Update the modified price of the items & item variants
     */
    public function updatePriceOnly($storeId)
    {
        $filters    = [
            ['field' => 'main_table.StoreId', 'value' => $storeId, 'condition_type' => 'eq']
        ];
        $criteria   = $this->replicationHelper->buildCriteriaGetUpdatedOnly($filters);
        $collection = $this->replPriceCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'ItemId',
            'ls_replication_repl_item',
            'nav_id'
        );
        if ($collection->getSize() > 0) {
            /** @var ReplPrice $replPrice */
            foreach ($collection as $replPrice) {
                try {
                    if (!$replPrice->getVariantId()) {
                        $sku = $replPrice->getItemId();
                    } else {
                        $sku = $replPrice->getItemId() . '-' . $replPrice->getVariantId();
                    }
                    $productData = $this->productRepository->get($sku);
                    if (isset($productData)) {
                        $productData->setPrice($replPrice->getUnitPrice());
                        // @codingStandardsIgnoreStart
                        $this->productResourceModel->saveAttribute($productData, 'price');
                        // @codingStandardsIgnoreEnd
                        if ($productData->getTypeId() == 'configurable') {
                            $_children = $productData->getTypeInstance()->getUsedProducts($productData);
                            foreach ($_children as $child) {
                                $childProductData = $this->productRepository->get($child->getSKU());
                                $childProductData->setPrice($replPrice->getUnitPrice());
                                // @codingStandardsIgnoreStart
                                $this->productResourceModel->saveAttribute($childProductData, 'price');
                                // @codingStandardsIgnoreEnd
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->logger->debug("Problem with sku: " . $sku . " in " . __METHOD__);
                    $this->logger->debug($e->getMessage());
                }
                $replPrice->setData('is_updated', '0');
                $replPrice->setData('processed', '1');
                $this->replPriceRepository->save($replPrice);
            }
        }
    }

    /**
     * @param $storeId
     * Update the inventory of the items & item variants
     */
    public function updateInventoryOnly($storeId)
    {
        $filters    = [
            ['field' => 'main_table.StoreId', 'value' => $storeId, 'condition_type' => 'eq']
        ];
        $criteria   = $this->replicationHelper->buildCriteriaGetUpdatedOnly($filters);
        $collection = $this->replInvStatusCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'ItemId',
            'ls_replication_repl_item',
            'nav_id'
        );
        if ($collection->getSize() > 0) {
            /** @var ReplInvStatus $replInvStatus */
            foreach ($collection as $replInvStatus) {
                try {
                    if (!$replInvStatus->getVariantId()) {
                        $sku = $replInvStatus->getItemId();
                    } else {
                        $sku = $replInvStatus->getItemId() . '-' . $replInvStatus->getVariantId();
                    }
                    $stockItem = $this->stockRegistry->getStockItemBySku($sku);
                    if (isset($stockItem)) {
                        // @codingStandardsIgnoreStart
                        $stockItem->setQty($replInvStatus->getQuantity());
                        $stockItem->setIsInStock(($replInvStatus->getQuantity() > 0) ? 1 : 0);
                        $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
                        // @codingStandardsIgnoreEnd
                    }
                } catch (Exception $e) {
                    $this->logger->debug("Problem with sku: " . $sku . " in " . __METHOD__);
                    $this->logger->debug($e->getMessage());
                }
                $replInvStatus->setData('is_updated', '0');
                $replInvStatus->setData('processed', '1');
                $this->replInvStatusRepository->save($replInvStatus);
            }
        }
    }

    /** For product variants, get image from item_image_link with type item variant
     * @param $configProduct
     * @param $item
     * @param $itemBarcodes
     * @param $variants
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function createConfigurableProducts($configProduct, $item, $itemBarcodes, $variants)
    {
        // get those attribute codes which are assigned to product.
        $attributesCode           = $this->_getAttributesCodes($item->getNavId());
        $attributesIds            = [];
        $associatedProductIds     = [];
        $configurableProductsData = [];
        foreach ($attributesCode as $value) {
            /** @var Interceptor $attribute */
            $attribute                             = $this->eavConfig->getAttribute('catalog_product', $value);
            $attributeOptions[$attribute->getId()] = $attribute->getSource()->getAllOptions();
            $attributesIds[]                       = $attribute->getId();
        }

        $storeId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
        /** @var ReplItemVariantRegistration $value */
        foreach ($variants as $value) {
            $sku = $value->getItemId() . '-' . $value->getVariantId();
            try {
                $productData = $this->productRepository->get($sku);
                try {
                    $name = $this->getNameForVariant($value, $item);
                    $productData->setName($name);
                    $productData->setMetaTitle($name);
                    $productData->setDescription($item->getDetails());
                    $productData->setWeight($item->getGrossWeight());
                    $productData->setCustomAttribute("uom", $value->getBaseUnitOfMeasure());
                    $productImages = $this->replicationHelper->getImageLinksByType(
                        $value->getItemId() . ',' . $value->getVariantId(),
                        'Item Variant'
                    );
                    if ($productImages) {
                        $this->logger->debug('Found images for the simple product ' . $sku);
                        $productData->setMediaGalleryEntries($this->getMediaGalleryEntries($productImages));
                    }
                    $productData->setStatus(Status::STATUS_ENABLED);
                    // @codingStandardsIgnoreStart
                    $this->productRepository->save($productData);
                    $value->setData('processed', '1');
                    $value->setData('is_updated', '0');
                    $this->replItemVariantRegistrationRepository->save($value);
                    // @codingStandardsIgnoreEnd
                } catch (Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            } catch (NoSuchEntityException $e) {
                $is_variant_contain_null = false;
                $d1                      = (($value->getVariantDimension1()) ? $value->getVariantDimension1() : '');
                $d2                      = (($value->getVariantDimension2()) ? $value->getVariantDimension2() : '');
                $d3                      = (($value->getVariantDimension3()) ? $value->getVariantDimension3() : '');
                $d4                      = (($value->getVariantDimension4()) ? $value->getVariantDimension4() : '');
                $d5                      = (($value->getVariantDimension5()) ? $value->getVariantDimension5() : '');
                $d6                      = (($value->getVariantDimension6()) ? $value->getVariantDimension6() : '');

                /** Check if all configurable attributes has value or not. */

                foreach ($attributesCode as $keyCode => $valueCode) {
                    if (${'d' . $keyCode} == '') {
                        // validation failed, that attribute contain some crappy data or null attribute which we does not need to process
                        $is_variant_contain_null = true;
                        break;
                    }
                }
                if ($is_variant_contain_null) {
                    //force override the value and continue the loop
                    $this->logger->debug("Variant issue : Item " . $value->getItemId() . '-' . $value->getVariantId() . " contain null attribute");
                    $value->setData('is_updated', '0');
                    $value->setData('processed', '1');
                    $this->replItemVariantRegistrationRepository->save($value);
                    continue;
                }

                $productV = $this->productFactory->create();

                $name = $this->getNameForVariant($value, $item);
                $productV->setName($name);
                $productV->setMetaTitle($name);
                $productV->setDescription($item->getDetails());
                $productV->setSku($sku);
                $productV->setWeight($item->getGrossWeight());
                $itemPrice = $this->getItemPrice($value->getItemId(), $value->getVariantId());
                if (isset($itemPrice)) {
                    $productV->setPrice($itemPrice->getUnitPrice());
                } else {
                    // Just in-case if we don't have price for Variant then in that case,
                    // we are using the price of main product.
                    $productV->setPrice($configProduct->getPrice());
                }
                $productV->setAttributeSetId(4);
                $productV->setWebsiteIds([1]);
                $productV->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
                $productV->setStatus(Status::STATUS_ENABLED);
                $productV->setTypeId('simple');
                foreach ($attributesCode as $keyCode => $valueCode) {
                    if (isset($keyCode) && $keyCode != '') {
                        $optionId = $this->_getOptionIDByCode($valueCode, ${'d' . $keyCode});
                        if (isset($optionId)) {
                            $productV->setData($valueCode, $optionId);
                        }
                    }
                }
                $productImages = $this->replicationHelper
                    ->getImageLinksByType($value->getItemId() . ',' . $value->getVariantId(), 'Item Variant');
                if ($productImages) {
                    $this->logger->debug('Found images for the simple product ' . $sku);
                    $productV->setMediaGalleryEntries($this->getMediaGalleryEntries($productImages));
                }

                $productV->setCustomAttribute('uom', $item->getBaseUnitOfMeasure());
                if (isset($itemBarcodes[$sku])) {
                    $productV->setCustomAttribute('barcode', $itemBarcodes[$sku]);
                }
                $itemStock = $this->getInventoryStatus($value->getItemId(), $storeId, $value->getVariantId());
                $productV->setStockData([
                    'use_config_manage_stock' => 1,
                    'is_in_stock'             => ($itemStock > 0) ? 1 : 0,
                    'is_qty_decimal'          => 0,
                    'qty'                     => $itemStock
                ]);
                /** @var ProductInterface $productSaved */
                // @codingStandardsIgnoreStart
                $productSaved           = $this->productRepository->save($productV);
                $associatedProductIds[] = $productSaved->getId();
                $value->setData('is_updated', '0');
                $value->setData('processed', '1');
                $this->replItemVariantRegistrationRepository->save($value);
                // @codingStandardsIgnoreEnd
            }
        }
        $productId = $configProduct->getId();
        foreach ($attributesIds as $attributeKey => $attributeId) {
            $data = [
                'attribute_id' => $attributeId,
                'product_id'   => $productId,
                'position'     => $attributeKey
            ];
            try {
                // @codingStandardsIgnoreStart
                $this->attribute->setData($data)->save();
                // @codingStandardsIgnoreEnd
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $configProduct->setTypeId("configurable"); // Setting Product Type As Configurable
        $configProduct->setAffectConfigurableProductAttributes(4);
        $this->configurable->setUsedProductAttributes($configProduct, $attributesIds);
        $configProduct->setNewVariationsAttributeSetId(4); // Setting Attribute Set Id
        $configProduct->setConfigurableProductsData($configurableProductsData);
        $configProduct->setCanSaveConfigurableAttributes(true);
        $configProduct->setAssociatedProductIds($associatedProductIds); // Setting Associated Products
        $configProduct->save();
    }

    /**
     * @param $itemId
     * @param $storeId
     * @param null $variantId
     * @return float|int
     */
    public function getInventoryStatus($itemId, $storeId, $variantId = null)
    {
        $qty = 0;
        try {
            $filters = [
                ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'],
                ['field' => 'StoreId', 'value' => $storeId, 'condition_type' => 'eq'],
            ];
            if (isset($variantId)) {
                $filters[] = ['field' => 'VariantId', 'value' => $variantId, 'condition_type' => 'eq'];
            } else {
                $filters[] = ['field' => 'VariantId', 'value' => true, 'condition_type' => 'null'];
            }
            $searchCriteria  = $this->replicationHelper->buildCriteriaForArray($filters, 1);
            $inventoryStatus = [];
            /** @var ReplInvStatusRepository $inventoryStatus */
            $inventoryStatus = $this->replInvStatusRepository->getList($searchCriteria)->getItems();
            if (!empty($inventoryStatus)) {
                $inventoryStatus = reset($inventoryStatus);
                /** @var ReplInvStatus $invStatus */
                $qty = $inventoryStatus->getQuantity();
                $inventoryStatus->setData('is_updated', '0');
                $inventoryStatus->setData('processed', '1');
                $this->replInvStatusRepository->save($inventoryStatus);
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        return $qty;
    }

    /**
     * @param $value
     * @param $item
     * @return string
     */
    public function getNameForVariant(
        ReplItemVariantRegistration $value,
        ReplItem $item
    ) {
        $d1 = (($value->getVariantDimension1()) ? $value->getVariantDimension1() : '');
        $d2 = (($value->getVariantDimension2()) ? $value->getVariantDimension2() : '');
        $d3 = (($value->getVariantDimension3()) ? $value->getVariantDimension3() : '');
        $d4 = (($value->getVariantDimension4()) ? $value->getVariantDimension4() : '');
        $d5 = (($value->getVariantDimension5()) ? $value->getVariantDimension5() : '');
        $d6 = (($value->getVariantDimension6()) ? $value->getVariantDimension6() : '');

        /** @var ProductInterface $productV */
        $dMerged = (($d1) ? '-' . $d1 : '') . (($d2) ? '-' . $d2 : '') . (($d3) ? '-' . $d3 : '') .
            (($d4) ? '-' . $d4 : '') . (($d5) ? '-' . $d5 : '') . (($d6) ? '-' . $d6 : '');
        $name    = $item->getDescription() . $dMerged;
        return $name;
    }
}
