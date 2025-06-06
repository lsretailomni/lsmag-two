<?php

namespace Ls\Replication\Cron;

use \Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Replication\Api\ReplAttributeValueRepositoryInterface;
use \Ls\Replication\Api\ReplBarcodeRepositoryInterface as ReplBarcodeRepository;
use \Ls\Replication\Api\ReplHierarchyLeafRepositoryInterface as ReplHierarchyLeafRepository;
use \Ls\Replication\Api\ReplImageLinkRepositoryInterface;
use \Ls\Replication\Api\ReplInvStatusRepositoryInterface as ReplInvStatusRepository;
use \Ls\Replication\Api\ReplItemRepositoryInterface as ReplItemRepository;
use \Ls\Replication\Api\ReplItemUnitOfMeasureRepositoryInterface as ReplItemUnitOfMeasure;
use \Ls\Replication\Api\ReplItemVariantRegistrationRepositoryInterface as ReplItemVariantRegistrationRepository;
use \Ls\Replication\Api\ReplLoyVendorItemMappingRepositoryInterface;
use \Ls\Replication\Api\ReplPriceRepositoryInterface as ReplPriceRepository;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplBarcode;
use \Ls\Replication\Model\ReplBarcodeSearchResults;
use \Ls\Replication\Model\ReplImageLink;
use \Ls\Replication\Model\ReplInvStatus;
use \Ls\Replication\Model\ReplItem;
use \Ls\Replication\Model\ReplItemSearchResults;
use \Ls\Replication\Model\ReplItemUnitOfMeasureSearchResultsFactory;
use \Ls\Replication\Model\ReplItemVariant;
use \Ls\Replication\Model\ReplItemVariantRegistration;
use \Ls\Replication\Model\ReplItemVariantRegistrationSearchResultsFactory;
use \Ls\Replication\Model\ReplItemVariantRepository;
use \Ls\Replication\Model\ResourceModel\ReplAttributeValue\CollectionFactory as ReplAttributeValueCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyLeaf\CollectionFactory as ReplHierarchyLeafCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplImageLink\CollectionFactory as ReplImageLinkCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplInvStatus\CollectionFactory as ReplInvStatusCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplItemUnitOfMeasure\CollectionFactory as ReplItemUomCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplItemVariant\CollectionFactory as ReplItemVariantCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplItemVariantRegistration\CollectionFactory as ItemVariantRegistrationCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplLoyVendorItemMapping\CollectionFactory as ReplItemVendorCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplPrice\CollectionFactory as ReplPriceCollectionFactory;
use \Ls\Replication\Service\ImportImageService;
use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\EntryConverterPool;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Gallery\Entry;
use Magento\Catalog\Model\Product\Gallery\UpdateHandlerFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogImportExport\Model\Import\Product\MediaGalleryProcessor as MediaProcessor;
use Magento\Catalog\Model\ProductRepository\MediaGalleryProcessor;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute\Interceptor;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProTypeModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\Data\AttributeGroupInterface;
use Magento\Eav\Model\AttributeManagement;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\GroupFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as EavAttributeCollectionFactory;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Filesystem;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Create Items in magento replicated from omni
 */
class ProductCreateTask
{
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

    /** @var ReplItemRepository */
    public $itemRepository;

    /** @var ReplBarcodeRepository */
    public $replBarcodeRepository;

    /** @var ReplImageLinkRepositoryInterface */
    public $replImageLinkRepositoryInterface;

    /** @var ReplHierarchyLeafRepository */
    public $replHierarchyLeafRepository;

    /** @var ReplPriceRepository */
    public $replPriceRepository;

    /** @var ReplItemUnitOfMeasure */
    public $replItemUomRepository;

    /** @var ReplInvStatusRepository */
    public $replInvStatusRepository;

    /** @var ProductAttributeMediaGalleryEntryInterface */
    public $attributeMediaGalleryEntry;

    /** @var ImageContentFactory */
    public $imageContent;

    /** @var SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var Logger */
    public $logger;

    /** @var LoyaltyHelper */
    public $loyaltyHelper;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var ReplAttributeValueRepositoryInterface */
    public $replAttributeValueRepositoryInterface;

    /** @var LSR */
    public $lsr;

    /** @var bool */
    public $cronStatus = false;

    /** @var ReplItemVariantRegistrationRepository */
    public $replItemVariantRegistrationRepository;

    /**  @var ReplInvStatusCollectionFactory */
    public $replInvStatusCollectionFactory;

    /** @var ReplPriceCollectionFactory */
    public $replPriceCollectionFactory;

    /** @var ReplItemUomCollectionFactory */
    public $replItemUomCollectionFactory;

    /** @var ItemVariantRegistrationCollectionFactory */
    public $replItemVariantRegistrationCollectionFactory;

    /** @var ReplHierarchyLeafCollectionFactory */
    public $replHierarchyLeafCollectionFactory;

    /** @var ReplImageLinkCollectionFactory */
    public $replImageLinkCollectionFactory;

    /**  @var ReplAttributeValueCollectionFactory */
    public $replAttributeValueCollectionFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    public $productResourceModel;

    /** @var CategoryLinkRepositoryInterface */
    public $categoryLinkRepositoryInterface;

    /** @var CollectionFactory */
    public $collectionFactory;

    /** @var CategoryRepositoryInterface */
    public $categoryRepository;

    /** @var int */
    public $remainingRecords;

    /**
     * @var MediaGalleryProcessor
     */
    public $mediaGalleryProcessor;

    /**
     * @var UpdateHandlerFactory
     */
    public $updateHandlerFactory;
    /**
     * @var EntryConverterPool
     */
    public $entryConverterPool;

    /** @var StoreInterface $store */
    public $store;

    /** @var int|bool */
    public $webStoreId = false;

    /**
     * @var Factory
     */
    public $optionsFactory;

    /**
     * @var AttributeManagement
     */
    public $attributeManagement;

    /**
     * @var AttributeGroupRepositoryInterface
     */
    public $attributeGroupRepository;

    /**
     * @var ReplItemUnitOfMeasureSearchResultsFactory
     */
    public $replItemUnitOfMeasureSearchResultsFactory;

    /**
     * @var ReplItemVariantRegistrationSearchResultsFactory
     */
    public $replItemVariantRegistrationSearchResultsFactory;

    /**
     * @var EavAttributeCollectionFactory
     */
    public $eavAttributeCollectionFactory;

    /**
     * @var ReplItemVendorCollectionFactory
     */
    public $replItemVendorCollectionFactory;

    /**
     * @var ReplLoyVendorItemMappingRepositoryInterface
     */
    public $replVendorItemMappingRepositoryInterface;

    /**
     * @var GroupFactory
     */
    public $attributeSetGroupFactory;

    /**
     * @var array
     */
    public array $imagesFetched;

    /**
     * @var Product\Media\Config
     */
    public $mediaConfig;
    /**
     * @var Filesystem
     */
    public Filesystem $filesystem;
    /**
     * @var Filesystem\Directory\WriteInterface
     */
    public Filesystem\Directory\WriteInterface $mediaDirectory;
    /**
     * @var ResourceConnection
     */
    public ResourceConnection $resourceConnection;
    /**
     * @var File
     */
    public File $file;
    /**
     * @var MediaProcessor
     */
    public MediaProcessor $mediaProcessor;

    /**
     * @var ImportImageService
     */
    public $imageService;

    /**
     * @var ReplItemVariantCollectionFactory
     */
    public $replItemVariantCollectionFactory;

    /**
     * @var ReplItemVariantRepository
     */
    public $replItemVariantRepository;
    /**
     * @var DataTranslationTask
     */
    public $dataTranslationTask;

    /**
     * @var SortOrderBuilder
     */
    public $sortOrderBuilder;

    /**
     * @var string
     */
    public $message;

    /**
     * @param Config $eavConfig
     * @param ConfigurableProTypeModel $configurable
     * @param Attribute $attribute
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductAttributeMediaGalleryEntryInterface $attributeMediaGalleryEntry
     * @param ImageContentFactory $imageContent
     * @param ReplItemRepository $itemRepository
     * @param ReplItemVariantRegistrationRepository $replItemVariantRegistrationRepository
     * @param ReplHierarchyLeafRepository $replHierarchyLeafRepository
     * @param ReplBarcodeRepository $replBarcodeRepository
     * @param ReplPriceRepository $replPriceRepository
     * @param ReplItemUnitOfMeasure $replItemUnitOfMeasureRepository
     * @param ReplInvStatusRepository $replInvStatusRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface
     * @param LoyaltyHelper $loyaltyHelper
     * @param ReplicationHelper $replicationHelper
     * @param ReplAttributeValueRepositoryInterface $replAttributeValueRepositoryInterface
     * @param ReplLoyVendorItemMappingRepositoryInterface $replVendorItemMappingRepositoryInterface
     * @param Logger $logger
     * @param LSR $LSR
     * @param ReplInvStatusCollectionFactory $replInvStatusCollectionFactory
     * @param ReplPriceCollectionFactory $replPriceCollectionFactory
     * @param ReplItemUomCollectionFactory $replItemUomCollectionFactory
     * @param ItemVariantRegistrationCollectionFactory $replItemVariantRegistrationCollectionFactory
     * @param ReplHierarchyLeafCollectionFactory $replHierarchyLeafCollectionFactory
     * @param ReplAttributeValueCollectionFactory $replAttributeValueCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResourceModel
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryLinkRepositoryInterface $categoryLinkRepositoryInterface
     * @param CollectionFactory $collectionFactory
     * @param ReplImageLinkCollectionFactory $replImageLinkCollectionFactory
     * @param MediaProcessor $mediaProcessor
     * @param MediaGalleryProcessor $mediaGalleryProcessor
     * @param UpdateHandlerFactory $updateHandlerFactory
     * @param EntryConverterPool $entryConverterPool
     * @param Factory $optionsFactory
     * @param AttributeManagement $attributeManagement
     * @param AttributeGroupRepositoryInterface $attributeGroupRepository
     * @param ReplItemUnitOfMeasureSearchResultsFactory $replItemUnitOfMeasureSearchResultsFactory
     * @param ReplItemVariantRegistrationSearchResultsFactory $replItemVariantRegistrationSearchResultsFactory
     * @param EavAttributeCollectionFactory $eavAttributeCollectionFactory
     * @param ReplItemVendorCollectionFactory $replItemVendorCollectionFactory
     * @param GroupFactory $attributeSetGroupFactory
     * @param Product\Media\Config $mediaConfig
     * @param ReplItemVariantCollectionFactory $replItemVariantCollectionFactory
     * @param ReplItemVariantRepository $replItemVariantRepository
     * @param Filesystem $filesystem
     * @param ResourceConnection $resourceConnection
     * @param File $file
     * @param DataTranslationTask $dataTranslationTask
     * @param ImportImageService $imageService
     * @param SortOrderBuilder $sortOrderBuilder
     * @throws FileSystemException
     */
    public function __construct(
        Config $eavConfig,
        Configurable $configurable,
        Attribute $attribute,
        ProductInterfaceFactory $productInterfaceFactory,
        ProductRepositoryInterface $productRepository,
        ProductAttributeMediaGalleryEntryInterface $attributeMediaGalleryEntry,
        ImageContentFactory $imageContent,
        ReplItemRepository $itemRepository,
        ReplItemVariantRegistrationRepository $replItemVariantRegistrationRepository,
        ReplHierarchyLeafRepository $replHierarchyLeafRepository,
        ReplBarcodeRepository $replBarcodeRepository,
        ReplPriceRepository $replPriceRepository,
        ReplItemUnitOfMeasure $replItemUnitOfMeasureRepository,
        ReplInvStatusRepository $replInvStatusRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface,
        LoyaltyHelper $loyaltyHelper,
        ReplicationHelper $replicationHelper,
        ReplAttributeValueRepositoryInterface $replAttributeValueRepositoryInterface,
        ReplLoyVendorItemMappingRepositoryInterface $replVendorItemMappingRepositoryInterface,
        Logger $logger,
        LSR $LSR,
        ReplInvStatusCollectionFactory $replInvStatusCollectionFactory,
        ReplPriceCollectionFactory $replPriceCollectionFactory,
        ReplItemUomCollectionFactory $replItemUomCollectionFactory,
        ItemVariantRegistrationCollectionFactory $replItemVariantRegistrationCollectionFactory,
        ReplHierarchyLeafCollectionFactory $replHierarchyLeafCollectionFactory,
        ReplAttributeValueCollectionFactory $replAttributeValueCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResourceModel,
        CategoryRepositoryInterface $categoryRepository,
        CategoryLinkRepositoryInterface $categoryLinkRepositoryInterface,
        CollectionFactory $collectionFactory,
        ReplImageLinkCollectionFactory $replImageLinkCollectionFactory,
        MediaProcessor $mediaProcessor,
        MediaGalleryProcessor $mediaGalleryProcessor,
        UpdateHandlerFactory $updateHandlerFactory,
        EntryConverterPool $entryConverterPool,
        Factory $optionsFactory,
        AttributeManagement $attributeManagement,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        ReplItemUnitOfMeasureSearchResultsFactory $replItemUnitOfMeasureSearchResultsFactory,
        ReplItemVariantRegistrationSearchResultsFactory $replItemVariantRegistrationSearchResultsFactory,
        EavAttributeCollectionFactory $eavAttributeCollectionFactory,
        ReplItemVendorCollectionFactory $replItemVendorCollectionFactory,
        GroupFactory $attributeSetGroupFactory,
        Product\Media\Config $mediaConfig,
        ReplItemVariantCollectionFactory $replItemVariantCollectionFactory,
        ReplItemVariantRepository $replItemVariantRepository,
        Filesystem $filesystem,
        ResourceConnection $resourceConnection,
        File $file,
        DataTranslationTask $dataTranslationTask,
        ImportImageService $imageService,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->eavConfig                                       = $eavConfig;
        $this->configurable                                    = $configurable;
        $this->attribute                                       = $attribute;
        $this->productFactory                                  = $productInterfaceFactory;
        $this->productRepository                               = $productRepository;
        $this->attributeMediaGalleryEntry                      = $attributeMediaGalleryEntry;
        $this->imageContent                                    = $imageContent;
        $this->itemRepository                                  = $itemRepository;
        $this->replItemVariantRegistrationRepository           = $replItemVariantRegistrationRepository;
        $this->replHierarchyLeafRepository                     = $replHierarchyLeafRepository;
        $this->replBarcodeRepository                           = $replBarcodeRepository;
        $this->replPriceRepository                             = $replPriceRepository;
        $this->replItemUomRepository                           = $replItemUnitOfMeasureRepository;
        $this->replInvStatusRepository                         = $replInvStatusRepository;
        $this->searchCriteriaBuilder                           = $searchCriteriaBuilder;
        $this->logger                                          = $logger;
        $this->replImageLinkRepositoryInterface                = $replImageLinkRepositoryInterface;
        $this->loyaltyHelper                                   = $loyaltyHelper;
        $this->replicationHelper                               = $replicationHelper;
        $this->replAttributeValueRepositoryInterface           = $replAttributeValueRepositoryInterface;
        $this->replVendorItemMappingRepositoryInterface        = $replVendorItemMappingRepositoryInterface;
        $this->lsr                                             = $LSR;
        $this->replInvStatusCollectionFactory                  = $replInvStatusCollectionFactory;
        $this->replPriceCollectionFactory                      = $replPriceCollectionFactory;
        $this->replItemUomCollectionFactory                    = $replItemUomCollectionFactory;
        $this->replItemVariantRegistrationCollectionFactory    = $replItemVariantRegistrationCollectionFactory;
        $this->replHierarchyLeafCollectionFactory              = $replHierarchyLeafCollectionFactory;
        $this->replAttributeValueCollectionFactory             = $replAttributeValueCollectionFactory;
        $this->productResourceModel                            = $productResourceModel;
        $this->categoryLinkRepositoryInterface                 = $categoryLinkRepositoryInterface;
        $this->collectionFactory                               = $collectionFactory;
        $this->categoryRepository                              = $categoryRepository;
        $this->replImageLinkCollectionFactory                  = $replImageLinkCollectionFactory;
        $this->mediaProcessor                                  = $mediaProcessor;
        $this->mediaGalleryProcessor                           = $mediaGalleryProcessor;
        $this->updateHandlerFactory                            = $updateHandlerFactory;
        $this->entryConverterPool                              = $entryConverterPool;
        $this->optionsFactory                                  = $optionsFactory;
        $this->attributeManagement                             = $attributeManagement;
        $this->attributeGroupRepository                        = $attributeGroupRepository;
        $this->replItemUnitOfMeasureSearchResultsFactory       = $replItemUnitOfMeasureSearchResultsFactory;
        $this->replItemVariantRegistrationSearchResultsFactory = $replItemVariantRegistrationSearchResultsFactory;
        $this->eavAttributeCollectionFactory                   = $eavAttributeCollectionFactory;
        $this->replItemVendorCollectionFactory                 = $replItemVendorCollectionFactory;
        $this->attributeSetGroupFactory                        = $attributeSetGroupFactory;
        $this->mediaConfig                                     = $mediaConfig;
        $this->replItemVariantCollectionFactory                = $replItemVariantCollectionFactory;
        $this->replItemVariantRepository                       = $replItemVariantRepository;
        $this->filesystem                                      = $filesystem;
        $this->mediaDirectory                                  = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->resourceConnection                              = $resourceConnection;
        $this->file                                            = $file;
        $this->dataTranslationTask                             = $dataTranslationTask;
        $this->imageService                                    = $imageService;
        $this->sortOrderBuilder                                = $sortOrderBuilder;
        $this->lsr->setFpcInvalidateFlag(true);
    }

    /**
     * Method responsible for creating items
     *
     * @param mixed $storeData
     * @throws InputException
     * @throws LocalizedException
     */
    public function execute($storeData = null)
    {
        if (!$this->lsr->isSSM()) {
            if (!empty($storeData) && $storeData instanceof StoreInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->lsr->getAllStores();
            }
        } else {
            $stores = [$this->lsr->getAdminStore()];
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_CRON_PRODUCT_CONFIG_PATH_LAST_EXECUTE,
                        $store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );

                    if ($this->isReady()) {
                        $this->logger->debug('Running ProductCreateTask for Store ' . $store->getName());
                        $this->setConfigurationDirectives();
                        $this->webStoreId = $this->lsr->getStoreConfig(
                            LSR::SC_SERVICE_STORE,
                            $store->getId()
                        );
                        $storeId          = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE, $store->getId());
                        $productBatchSize = $this->lsr->getStoreConfig(
                            LSR::SC_REPLICATION_PRODUCT_BATCHSIZE,
                            $store->getId()
                        );
                        $criteria         = $this->replicationHelper->buildCriteriaForNewItems(
                            'scope_id',
                            $this->getScopeId(),
                            'eq',
                            $productBatchSize
                        );
                        /** @var ReplItemSearchResults $items */
                        $items = $this->itemRepository->getList($criteria);
                        /** @var ReplItem $item */
                        foreach ($items->getItems() as $item) {
                            try {
                                $taxClass    = null;
                                $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                                    $item->getNavId(),
                                    '',
                                    '',
                                    'global'
                                );

                                $langCode = $this->lsr->getStoreConfig(
                                    LSR::SC_STORE_DATA_TRANSLATION_LANG_CODE,
                                    $store->getId()
                                );
                                $this->dataTranslationTask->updateItem(
                                    $store,
                                    $langCode,
                                    $item->getNavId()
                                );

                                $productData->setName($item->getDescription());
                                $productData->setMetaTitle($item->getDescription());
                                $productData->setDescription($item->getDetails());

                                if (!empty($item->getTaxItemGroupId())) {
                                    $taxClass = $this->replicationHelper->getTaxClassGivenName(
                                        $item->getTaxItemGroupId()
                                    );
                                }
                                $websitesProduct = $productData->getWebsiteIds();
                                /** Check if item exist in the website and assign it if it doesn't exist*/
                                if (!in_array($store->getWebsiteId(), $websitesProduct, true)) {
                                    $websitesProduct[] = $store->getWebsiteId();
                                    $productData->setWebsiteIds($websitesProduct);
                                }

                                $productData->setWeight($item->getGrossWeight());
                                if (!empty($taxClass)) {
                                    $productData->setTaxClassId($taxClass->getClassId());
                                }
                                $productData->setAttributeSetId($productData->getAttributeSetId());
                                $productData->setCountryOfManufacture($item->getCountryOfOrigin());
                                $productData->setCustomAttribute(
                                    LSR::LS_TARIFF_NO_ATTRIBUTE_CODE,
                                    $item->getTariffNo()
                                );
                                $productData->setCustomAttribute(
                                    LSR::LS_ITEM_PRODUCT_GROUP,
                                    $item->getProductGroupId()
                                );
                                $productData->setCustomAttribute(
                                    LSR::LS_ITEM_CATEGORY,
                                    $item->getItemCategoryCode()
                                );
                                $productData->setCustomAttribute(
                                    LSR::LS_ITEM_SPECIAL_GROUP,
                                    $item->getSpecialGroups()
                                );
                                if (($item->getBaseUnitOfMeasure() != $item->getSalseUnitOfMeasure()) &&
                                    !empty($item->getSalseUnitOfMeasure())) {
                                    $productData->setCustomAttribute('uom', $item->getSalseUnitOfMeasure());
                                } else {
                                    $productData->setCustomAttribute('uom', $item->getBaseUnitOfMeasure());
                                }
                                $productData->setCustomAttribute(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $item->getNavId());
                                $product = $this->setProductStatus($productData, $item->getBlockedOnECom());
                                $product = $this->replicationHelper->manageStock(
                                    $product,
                                    $item->getType()
                                );

                                $variants             = $this->getNewOrUpdatedProductVariants(-1, $item->getNavId());
                                $totalUomCodes        = $this->replicationHelper->getUomCodes(
                                    $item->getNavId(),
                                    $this->getScopeId()
                                );
                                $uomCodesNotProcessed = $this->getNewOrUpdatedProductUoms(-1, $item->getNavId());
                                //Update UOM attributes for simple products
                                if (empty($variants) && count($totalUomCodes[$item->getNavId()]) == 1) {
                                    foreach ($uomCodesNotProcessed as $uomCode) {
                                        if (!empty($uomCode)) {
                                            $this->syncUomAdditionalAttributes($product, $uomCode, $item);
                                        }
                                    }
                                }

                                try {
                                    // @codingStandardsIgnoreLine
                                    $productSaved = $this->productRepository->save($product);
                                    $this->updateProductStatusGlobal($productSaved);
                                } catch (Exception $e) {
                                    $this->logDetailedException(
                                        __METHOD__,
                                        $this->store->getName(),
                                        $item->getNavId()
                                    );
                                    $this->logger->debug($e->getMessage());
                                    $item->setData('is_failed', 1);
                                    if (!empty($uomCode)) {
                                        $uomCode->setData('is_failed', 1);
                                    }
                                }
                                $item->setData('is_updated', 0);
                                $item->setData('processed_at', $this->replicationHelper->getDateTime());
                                $item->setData('processed', 1);
                                $this->itemRepository->save($item);

                                if (!empty($uomCode)) {
                                    $uomCode->setData('processed_at', $this->replicationHelper->getDateTime());
                                    $uomCode->setData('processed', 1);
                                    $uomCode->setData('is_updated', 0);
                                    $this->replItemUomRepository->save($uomCode);
                                }
                            } catch (NoSuchEntityException $e) {
                                /** @var Product $product */
                                $product = $this->productFactory->create();
                                $this->populateDefaultProductAttributes($product, $item);
                                $itemPrice = $this->getItemPrice($item->getNavId());
                                if (isset($itemPrice)) {
                                    $product->setPrice($itemPrice->getUnitPriceInclVat());
                                } else {
                                    $product->setPrice($item->getUnitPrice());
                                }
                                $itemStock = $this->replicationHelper->getInventoryStatus(
                                    $item->getNavId(),
                                    $storeId,
                                    $this->getScopeId()
                                );
                                $product   = $this->replicationHelper->manageStock(
                                    $product,
                                    $item->getType()
                                );

                                $product->setCustomAttribute(
                                    LSR::LS_TARIFF_NO_ATTRIBUTE_CODE,
                                    $item->getTariffNo()
                                );
                                $product->setCustomAttribute(
                                    LSR::LS_ITEM_PRODUCT_GROUP,
                                    $item->getProductGroupId()
                                );
                                $product->setCustomAttribute(
                                    LSR::LS_ITEM_CATEGORY,
                                    $item->getItemCategoryCode()
                                );
                                $product->setCustomAttribute(
                                    LSR::LS_ITEM_SPECIAL_GROUP,
                                    $item->getSpecialGroups()
                                );
                                if (($item->getBaseUnitOfMeasure() != $item->getSalseUnitOfMeasure()) &&
                                    !empty($item->getSalseUnitOfMeasure())) {
                                    $product->setCustomAttribute('uom', $item->getSalseUnitOfMeasure());
                                } else {
                                    $product->setCustomAttribute('uom', $item->getBaseUnitOfMeasure());
                                }
                                $product->setCustomAttribute(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $item->getNavId());
                                /** @var ReplBarcodeRepository $itemBarcodes */
                                $itemBarcodes = $this->_getBarcode($item->getNavId());

                                if (isset($itemBarcodes[$item->getNavId()])) {
                                    $product->setCustomAttribute('barcode', $itemBarcodes[$item->getNavId()]);
                                }

                                $totalUomCodes = $this->replicationHelper->getUomCodes(
                                    $item->getNavId(),
                                    $this->getScopeId()
                                );

                                $variants             = $this->getNewOrUpdatedProductVariants(-1, $item->getNavId());
                                $uomCodesNotProcessed = $this->getNewOrUpdatedProductUoms(-1, $item->getNavId());

                                //Set UOM attributes for simple products
                                if (empty($variants) && count($totalUomCodes[$item->getNavId()]) == 1) {
                                    foreach ($uomCodesNotProcessed as $uomCode) {
                                        if (!empty($uomCode)) {
                                            $this->syncUomAdditionalAttributes($product, $uomCode, $item);
                                        }
                                    }
                                }

                                try {
                                    // @codingStandardsIgnoreLine
                                    $this->logger->debug('Trying to save product ' . $item->getNavId() . ' in store ' . $store->getName());
                                    /** @var ProductRepositoryInterface $productSaved */
                                    $productSaved = $this->productRepository->save($product);

                                    if ($itemStock) {
                                        $this->replicationHelper->updateInventory($productSaved, $itemStock);
                                    }

                                    if (!empty($variants) || count($totalUomCodes[$item->getNavId()]) > 1) {
                                        $this->createConfigurableProducts(
                                            $productSaved,
                                            $item,
                                            $itemBarcodes,
                                            $variants,
                                            $totalUomCodes,
                                            $uomCodesNotProcessed
                                        );
                                    }

                                    $uomCodes = $this->getUomCodesProcessed($item->getNavId());
                                    $this->replicationHelper->getProductAttributes(
                                        $item->getNavId(),
                                        $this->getScopeId(),
                                        $this->productRepository,
                                        $uomCodes
                                    );
                                    $this->replicationHelper->assignProductToCategories($productSaved, $this->store);
                                    if (!empty($taxClass)) {
                                        $this->replicationHelper->assignTaxClassToChildren(
                                            $productSaved,
                                            $taxClass,
                                            $this->store->getId()
                                        );
                                    }
                                } catch (Exception $e) {
                                    $this->logDetailedException(
                                        __METHOD__,
                                        $this->store->getName(),
                                        $item->getNavId()
                                    );
                                    $this->logger->debug($e->getMessage());
                                    $item->setData('is_failed', 1);
                                    if (!empty($uomCode)) {
                                        $uomCode->setData('is_failed', 1);
                                    }
                                }
                                $item->setData('processed_at', $this->replicationHelper->getDateTime());
                                $item->setData('processed', 1);
                                $item->setData('is_updated', 0);
                                $this->itemRepository->save($item);

                                if (!empty($uomCode)) {
                                    $uomCode->setData('processed_at', $this->replicationHelper->getDateTime());
                                    $uomCode->setData('processed', 1);
                                    $uomCode->setData('is_updated', 0);
                                    $this->replItemUomRepository->save($uomCode);
                                }
                            }
                        }
                        if ($items->getTotalCount() == 0) {
                            $this->caterItemsRemoval();
                            $fullReplicationVariantStatus         = $this->lsr->getConfigValueFromDb(
                                ReplEcommItemVariantRegistrationsTask::CONFIG_PATH_STATUS,
                                ScopeInterface::SCOPE_WEBSITES,
                                $this->getScopeId()
                            );
                            $fullReplicationStandardVariantStatus = $this->lsr->getConfigValueFromDb(
                                LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT,
                                ScopeInterface::SCOPE_STORES,
                                $store->getId()
                            );
                            if ($fullReplicationVariantStatus == 1) {
                                $this->updateVariantsOnly();
                                $this->caterVariantsRemoval();
                            }
                            $fullReplicationItemUnitOfMeasure = $this->lsr->getConfigValueFromDb(
                                ReplEcommItemUnitOfMeasuresTask::CONFIG_PATH_STATUS,
                                ScopeInterface::SCOPE_WEBSITES,
                                $this->getScopeId()
                            );
                            if ($fullReplicationItemUnitOfMeasure == 1) {
                                $this->caterUomsRemoval();
                            }
                            if ($fullReplicationStandardVariantStatus == 1) {
                                $this->updateStandardVariantsOnly();
                            }
                            $this->updateBarcodeOnly();
                        }
                        if ($this->getRemainingRecords($store) == 0) {
                            $this->cronStatus = true;
                        }
                        $this->logger->debug('End ProductCreateTask for Store ' . $store->getName());
                    } else {
                        $this->logCronNotReadyReason();
                    }
                    // @codingStandardsIgnoreLine
                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_PRODUCT,
                        $store->getId(),
                        false,
                        ScopeInterface::SCOPE_STORES
                    );
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * Method responsible for executing cron manually from admin cron grid
     *
     * @param null $storeData
     * @return array
     * @throws InputException
     * @throws LocalizedException
     */
    public function executeManually($storeData = null)
    {
        $this->message = '';
        $this->execute($storeData);
        if (!empty($this->message)) {
            return [$this->message];
        }
        $itemsLeftToProcess = (int)$this->getRemainingRecords($storeData);
        return [$itemsLeftToProcess];
    }

    /**
     * Check if ready to process items
     *
     * @return bool
     */
    public function isReady()
    {
        list(
            $fullReplicationImageLinkStatus,
            $fullReplicationBarcodeStatus,
            $fullReplicationPriceStatus,
            $fullReplicationInvStatus,
            $cronCategoryCheck,
            $cronAttributeCheck,
            $cronAttributeVariantCheck
            ) = $this->getDependentCronsStatus();

        return $cronCategoryCheck == 1 &&
            $cronAttributeCheck == 1 &&
            $cronAttributeVariantCheck == 1 &&
            $fullReplicationImageLinkStatus == 1 &&
            $fullReplicationBarcodeStatus == 1 &&
            $fullReplicationPriceStatus == 1 &&
            $fullReplicationInvStatus == 1;
    }

    /**
     * Get all the dependent crons status
     *
     * @return array
     */
    public function getDependentCronsStatus()
    {
        $fullReplicationImageLinkStatus = $this->lsr->getConfigValueFromDb(
            ReplEcommImageLinksTask::CONFIG_PATH_STATUS,
            ScopeInterface::SCOPE_WEBSITES,
            $this->getScopeId()
        );
        $fullReplicationBarcodeStatus   = $this->lsr->getConfigValueFromDb(
            ReplEcommBarcodesTask::CONFIG_PATH_STATUS,
            ScopeInterface::SCOPE_WEBSITES,
            $this->getScopeId()
        );
        $fullReplicationPriceStatus     = $this->lsr->getConfigValueFromDb(
            ReplEcommPricesTask::CONFIG_PATH_STATUS,
            ScopeInterface::SCOPE_WEBSITES,
            $this->getScopeId()
        );
        $fullReplicationInvStatus       = $this->lsr->getConfigValueFromDb(
            ReplEcommInventoryStatusTask::CONFIG_PATH_STATUS,
            ScopeInterface::SCOPE_WEBSITES,
            $this->getScopeId()
        );
        $cronCategoryCheck              = $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_CATEGORY,
            ScopeInterface::SCOPE_STORES,
            $this->store->getId()
        );
        $cronAttributeCheck             = $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_ATTRIBUTE,
            ScopeInterface::SCOPE_STORES,
            $this->store->getId()
        );
        $cronAttributeVariantCheck      = $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
            ScopeInterface::SCOPE_STORES,
            $this->store->getId()
        );

        return [
            $fullReplicationImageLinkStatus,
            $fullReplicationBarcodeStatus,
            $fullReplicationPriceStatus,
            $fullReplicationInvStatus,
            $cronCategoryCheck,
            $cronAttributeCheck,
            $cronAttributeVariantCheck
        ];
    }

    /**
     * Setting configuration directives
     *
     * @return void
     */
    public function setConfigurationDirectives()
    {
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
    }

    /**
     * Log cron not ready reason
     *
     * @return void
     */
    public function logCronNotReadyReason()
    {
        list(
            $fullReplicationImageLinkStatus,
            $fullReplicationBarcodeStatus,
            $fullReplicationPriceStatus,
            $fullReplicationInvStatus,
            $cronCategoryCheck,
            $cronAttributeCheck,
            $cronAttributeVariantCheck
            ) = $this->getDependentCronsStatus();
        $this->message .= 'Product Replication cron fails because dependent crons were not executed successfully for Store ' . $this->store->getName() . ':';
        $this->message .= ((int)$cronCategoryCheck) ? '' : "\nrepl_categories,";
        $this->message .= ((int)$cronAttributeCheck) ? '' : ".\nrepl_attributes,";
        $this->message .= ((int)$cronAttributeVariantCheck) ? '' : "\nrepl_attributes,";
        $this->message .= ((int)$fullReplicationImageLinkStatus) ? '' : "\nrepl_image_link,";
        $this->message .= ((int)$fullReplicationBarcodeStatus) ? '' : "\nrepl_barcode,";
        $this->message .= ((int)$fullReplicationPriceStatus) ? '' : "\nrepl_price,";
        $this->message .= ((int)$fullReplicationInvStatus) ? '' : "\nrepl_inv_status";
        $this->message = rtrim($this->message, ',');
        // @codingStandardsIgnoreLine
        $this->logger->debug($this->message);
    }

    /**
     * Get identifier based on attribute set mechanism
     *
     * @param $item
     * @param $attributeSetsMechanism
     * @return mixed
     */
    public function getIdentifierBasedOnAttributeSetMechanism($item, $attributeSetsMechanism)
    {
        return $attributeSetsMechanism == LSR::SC_REPLICATION_ATTRIBUTE_SET_ITEM_CATEGORY_CODE ?
            $item->getItemCategoryCode() : $item->getProductGroupId();
    }

    /**
     * This function is overriding in enterprise module
     *
     * Get Default Product Type
     *
     * @param $item
     * @return string
     */
    public function getDefaultProductType($item)
    {
        return Type::TYPE_SIMPLE;
    }

    /**
     * This function is overriding in enterprise module
     *
     * Populate default product attributes
     *
     * @param $product
     * @param $item
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function populateDefaultProductAttributes(&$product, $item)
    {
        $attributeSetsMechanism = $this->replicationHelper->getAttributeSetsMechanism();
        $identifier             = $this->getIdentifierBasedOnAttributeSetMechanism($item, $attributeSetsMechanism);

        if (!$identifier) {
            $identifier = LSR::SC_REPLICATION_ATTRIBUTE_SET_EXTRAS . '_' .
                $this->store->getId();
        }
        $attributeSetId = $this->replicationHelper->getAttributeSetId(
            $attributeSetsMechanism,
            'ls_replication_repl_item',
            $this->store->getId(),
            $identifier
        );

        $product->setStoreId(0);
        $product->setWebsiteIds([$this->store->getWebsiteId()]);
        $product->setName($item->getDescription());
        $product->setMetaTitle($item->getDescription());
        $product->setSku($item->getNavId());
        $product->setUrlKey(
            $this->replicationHelper->oSlug($item->getDescription() . '-' . $item->getNavId())
        );
        $product->setVisibility(Visibility::VISIBILITY_BOTH);
        $product->setWeight($item->getGrossWeight());
        $product->setDescription($item->getDetails());
        $product->setCountryOfManufacture($item->getCountryOfOrigin());

        if (!empty($item->getTaxItemGroupId())) {
            $taxClass = $this->replicationHelper->getTaxClassGivenName(
                $item->getTaxItemGroupId()
            );

            if (!empty($taxClass)) {
                $product->setTaxClassId($taxClass->getClassId());
            }
        }

        $product->setAttributeSetId($attributeSetId);
        $product = $this->setProductStatus($product, $item->getBlockedOnECom());
        $typeId  = $this->getDefaultProductType($item);
        $product->setTypeId($typeId);
    }

    /**
     * Fetching base64 images from Central
     *
     * @param $productImages
     * @param $productData
     * @return array
     * @throws NoSuchEntityException
     */
    public function getMediaGalleryEntries($productImages, $productData)
    {
        $galleryArray = [];
        /** @var ReplImageLink $image */
        $i = 0;
        foreach ($productImages as $image) {
            if ($image->getIsDeleted() == 1) {
                $cacheId = $this->loyaltyHelper->getImageCacheId($image->getImageId());
                $this->loyaltyHelper->cacheHelper->removeCachedContent($cacheId);
                $image->setData('processed_at', $this->replicationHelper->getDateTime());
                $image->setData('processed', 1);
                $image->setData('is_updated', 0);
                // @codingStandardsIgnoreLine
                $this->replImageLinkRepositoryInterface->save($image);
                continue;
            }
            $types           = [];
            $imageSize       = [
                'height' => $this->lsr->getStoreConfig(
                    LSR::SC_REPLICATION_DEFAULT_ITEM_IMAGE_HEIGHT,
                    $this->store->getId()
                ),
                'width'  => $this->lsr->getStoreConfig(
                    LSR::SC_REPLICATION_DEFAULT_ITEM_IMAGE_WIDTH,
                    $this->store->getId()
                )
            ];
            $imageSizeObject = $this->loyaltyHelper->getImageSize($imageSize);
            if (!array_key_exists($image->getImageId(), $this->imagesFetched)) {
                $result = $this->loyaltyHelper->getImageById($image->getImageId(), $imageSizeObject);
                if (!empty($result) && !empty($result['format']) && !empty($result['image'])) {
                    $mimeType = $this->getMimeType($result['image']);
                    if ($this->replicationHelper->isMimeTypeValid($mimeType)) {
                        $imageContent = $this->imageContent->create()
                            ->setBase64EncodedData($result['image'])
                            ->setName($this->replicationHelper->oSlug($image->getImageId()))
                            ->setType($mimeType);
                        $this->attributeMediaGalleryEntry->setMediaType('image')
                            ->setLabel(($image->getDescription()) ?: $productData->getName())
                            ->setPosition($image->getDisplayOrder())
                            ->setDisabled(false)
                            ->setContent($imageContent);

                        if (version_compare($this->lsr->getOmniVersion(), '2023.05.1', '>=')) {
                            $this->attributeMediaGalleryEntry
                                ->setLabel(($image->getImageDescription()) ?: $productData->getName());
                        }

                        if ($i == 0) {
                            $types = ['image', 'small_image', 'thumbnail'];
                        }
                        $this->attributeMediaGalleryEntry->setTypes($types);
                        $galleryArray[]                            = clone $this->attributeMediaGalleryEntry;
                        $this->imagesFetched[$image->getImageId()] = $galleryArray[$i];
                        $i++;
                    } else {
                        $image->setData('is_failed', 1);
                        $this->logger->debug('MIME Type is not valid for Image Id : ' . $image->getImageId());
                    }
                } elseif (!empty($result) && !empty($result['location'])) {
                    if ($i == 0) {
                        $types = ['image', 'small_image', 'thumbnail'];
                    }
                    $galleryArray[]                            = [
                        'location'           => $result['location'],
                        'types'              => $types,
                        'repl_image_link_id' => $image->getId()
                    ];
                    $this->imagesFetched[$image->getImageId()] = $galleryArray[$i];
                    $i++;
                } else {
                    $image->setData('is_failed', 1);
                    $this->logger->debug('MIME Type is not valid for Image Id : ' . $image->getImageId());
                }
            } else {
                $existentImage = $this->imagesFetched[$image->getImageId()];
                $existentImage->setLabel(($image->getDescription()) ?: $productData->getName());

                if (version_compare($this->lsr->getOmniVersion(), '2023.05.1', '>=')) {
                    $existentImage->setLabel(($image->getImageDescription()) ?: $productData->getName());
                }

                if ($i == 0) {
                    $types = ['image', 'small_image', 'thumbnail'];
                    if (!($existentImage instanceof Entry)) {
                        $existentImage['types'] = $types;
                    } else {
                        $existentImage->setTypes($types);
                    }
                }

                $galleryArray[] = $existentImage;
                $this->logger->debug('Image corresponding to Image Id is already fetched: ' . $image->getImageId());
                $i++;
            }

            $image->setData('processed_at', $this->replicationHelper->getDateTime());
            $image->setData('processed', 1);
            $image->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->replImageLinkRepositoryInterface->save($image);
        }
        return $galleryArray;
    }

    /**
     * Getting mime type
     *
     * @param $image64
     * @return string
     */
    private function getMimeType($image64)
    {
        // @codingStandardsIgnoreLine
        return finfo_buffer(finfo_open(), base64_decode($image64), FILEINFO_MIME_TYPE);
    }

    /**
     * Getting new or updated product variants
     *
     * @param $pageSize
     * @param $itemId
     * @return mixed
     */
    private function getNewOrUpdatedProductVariants($pageSize = 100, $itemId = null)
    {
        $filters = [
            ['field' => 'main_table.VariantId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
            ['field' => 'second.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
        ];
        if (isset($itemId)) {
            $filters[] = ['field' => 'main_table.ItemId', 'value' => $itemId, 'condition_type' => 'eq'];
        } else {
            $filters[] = ['field' => 'main_table.ItemId', 'value' => true, 'condition_type' => 'notnull'];
        }

        $collection = $this->replItemVariantRegistrationCollectionFactory->create();

        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, $pageSize);
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'ItemId',
            'ls_replication_repl_item',
            'nav_id',
            false,
            false
        );

        $resultFactory = $this->replItemVariantRegistrationSearchResultsFactory->create();

        return $this->replicationHelper->setCollection($collection, $criteria, $resultFactory);
    }

    /**
     * Getting all available product variants
     *
     * @param null $itemId
     * @return mixed
     */
    public function getProductVariants($itemId = null)
    {
        $filters = [
            ['field' => 'VariantId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
        ];
        if (isset($itemId)) {
            $filters[] = ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'];
        } else {
            $filters[] = ['field' => 'ItemId', 'value' => true, 'condition_type' => 'notnull'];
        }
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        return $this->replItemVariantRegistrationRepository->getList($criteria)->getItems();
    }

    /**
     * Getting all available product variants
     *
     * @param null $itemId
     * @return mixed
     */
    public function getStandardProductVariants($itemId = null)
    {
        $filters = [
            ['field' => 'VariantId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'Description2', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
        ];
        if (isset($itemId)) {
            $filters[] = ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'];
        } else {
            $filters[] = ['field' => 'ItemId', 'value' => true, 'condition_type' => 'notnull'];
        }
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);

        return $this->replItemVariantRepository->getList($criteria)->getItems();
    }

    /**
     * Getting new or updated product uoms
     *
     * @param $pageSize
     * @param $itemId
     * @param $needAll
     * @return mixed
     */
    public function getNewOrUpdatedProductUoms($pageSize = 100, $itemId = null, $needAll = false)
    {
        $filters = [
            ['field' => 'main_table.Code', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
            ['field' => 'second.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
        ];
        if (isset($itemId)) {
            $filters[] = ['field' => 'main_table.ItemId', 'value' => $itemId, 'condition_type' => 'eq'];
        } else {
            $filters[] = ['field' => 'main_table.ItemId', 'value' => true, 'condition_type' => 'notnull'];
        }
        $collection = $this->replItemUomCollectionFactory->create();

        if ($needAll) {
            $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, $pageSize);
        } else {
            $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, $pageSize);
        }

        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'ItemId',
            'ls_replication_repl_item',
            'nav_id',
            false,
            false
        );

        $resultFactory = $this->replItemUnitOfMeasureSearchResultsFactory->create();

        return $this->replicationHelper->setCollection($collection, $criteria, $resultFactory, ['Order', 'QtyPrUom']);
    }

    /**
     * Return all deleted items only
     *
     * @param type $filters
     * @return type
     */
    private function getDeletedItemsOnly($filters)
    {
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly($filters);
        return $this->itemRepository->getList($criteria);
    }

    /**
     * Return all deleted variants only
     *
     * @param array $filters
     * @return type
     */
    private function getDeletedVariantsOnly($filters)
    {
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly($filters);
        return $this->replItemVariantRegistrationRepository->getList($criteria)->getItems();
    }

    /**
     * Return all deleted uom variants only
     *
     * @param array $filters
     * @return type
     */
    private function getDeletedUomsOnly($filters)
    {
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly($filters);
        return $this->replItemUomRepository->getList($criteria)->getItems();
    }

    /**
     * Return all the barcodes including the variant
     *
     * @param $itemId
     * @return array
     */
    public function _getBarcode($itemId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('ItemId', $itemId)
            ->addFilter('scope_id', $this->getScopeId(), 'eq')->create();
        $allBarCodes    = [];
        /** @var ReplBarcodeRepository $itemBarcodes */
        $itemBarcodes = $this->replBarcodeRepository->getList($searchCriteria)->getItems();
        foreach ($itemBarcodes as $itemBarcode) {
            $sku = $itemBarcode->getItemId() .
                (($itemBarcode->getVariantId()) ? '-' . $itemBarcode->getVariantId() : '');

            if (!empty($itemBarcode->getUnitOfMeasure())) {
                $sku = $sku . '-' . $itemBarcode->getUnitOfMeasure();
            }

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
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('nav_id', $itemId)->addFilter(
            'scope_id',
            $this->getScopeId(),
            'eq'
        )->create();
        /** @var ReplItemRepository $items */
        $items = $this->itemRepository->getList($searchCriteria)->getItems();
        foreach ($items as $item) {
            return $item;
        }
    }

    /**
     * Getting given item/variant price
     *
     * @param $itemId
     * @param null $variantId
     * @param null $unitOfMeasure
     * @param int $process
     * @return mixed
     */
    public function getItemPrice($itemId, $variantId = null, $unitOfMeasure = null, $process = 1)
    {
        $parameter  = null;
        $parameter2 = null;

        $filters = [
            ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'StoreId', 'value' => $this->webStoreId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
        ];

        if ($variantId) {
            $parameter = ['field' => 'VariantId', 'value' => $variantId, 'condition_type' => 'eq'];
        }

        if ($unitOfMeasure) {
            $parameter = ['field' => 'UnitOfMeasure', 'value' => $unitOfMeasure, 'condition_type' => 'eq'];
        }

        if (isset($unitOfMeasure) && isset($variantId)) {
            $parameter  = ['field' => 'UnitOfMeasure', 'value' => $unitOfMeasure, 'condition_type' => 'eq'];
            $parameter2 = ['field' => 'VariantId', 'value' => $variantId, 'condition_type' => 'eq'];
        }

        $item           = null;
        $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, 1, 1, $parameter, $parameter2);
        /** @var ReplPriceRepository $items */
        try {
            $items = $this->replPriceRepository->getList($searchCriteria)->getItems();
            if (!empty($items)) {
                $item = reset($items);
                /** @var ReplInvStatus $invStatus */

                if ($process) {
                    $item->setData('is_updated', 0);
                    $item->setData('processed', 1);
                    $item->setData('processed_at', $this->replicationHelper->getDateTime());
                } else {
                    $item->setData('is_updated', 1);
                    $item->setData('processed', 0);
                    $item->setData('is_failed', 0);
                }

                $this->replPriceRepository->save($item);
            }
        } catch (Exception $e) {
            $this->logDetailedException(
                __METHOD__,
                $this->store->getName(),
                $itemId,
                $variantId,
                $unitOfMeasure
            );
            $this->logger->debug($e->getMessage());
        }
        return $item;
    }

    /**
     * Getting only processed uom codes of the item
     *
     * @param $itemId
     * @return array
     */
    public function getUomCodesProcessed($itemId)
    {
        $filters = [
            ['field' => 'main_table.ItemId', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
            ['field' => 'main_table.processed', 'value' => 1, 'condition_type' => 'eq'],
            ['field' => 'main_table.is_updated', 'value' => 0, 'condition_type' => 'eq'],
            ['field' => 'second.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
        ];

        $itemUom = [];
        /** @var  $collection */
        $collection = $this->replItemUomCollectionFactory->create();
        $criteria   = $this->replicationHelper->buildCriteriaForDirect($filters, -1);

        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'ItemId',
            'ls_replication_repl_item',
            'nav_id',
            false,
            false
        );

        $collection->getSelect()->columns('second.BaseUnitOfMeasure');

        /** @var ReplItemUnitOfMeasure $items */
        try {
            if ($collection->getSize() > 0) {
                foreach ($collection->getItems() as $item) {
                    $uomDescription = $this->replicationHelper->getUomDescription($item);
                    /** @var \Ls\Replication\Model\ReplItemUnitOfMeasure $item */
                    $itemUom[$itemId][$uomDescription]            = $item->getCode();
                    $itemUom[$itemId . '-' . 'BaseUnitOfMeasure'] = $item->getData('BaseUnitOfMeasure');
                }
            }
        } catch (Exception $e) {
            $this->logDetailedException(
                __METHOD__,
                $this->store->getName(),
                $itemId
            );
            $this->logger->debug($e->getMessage());
        }
        return $itemUom;
    }

    /**
     * Update/Add the modified/added variants of the item
     */
    public function updateVariantsOnly()
    {
        $batchSize          = $this->replicationHelper->getVariantBatchSize();
        $allUpdatedVariants = $this->getNewOrUpdatedProductVariants($batchSize);
        $uomCodes           = $this->getNewOrUpdatedProductUoms($batchSize);
        $items              = [];
        if (!empty($allUpdatedVariants)) {
            foreach ($allUpdatedVariants as $variant) {
                $items[] = $variant->getItemId();
            }
        }
        if (!empty($uomCodes)) {
            foreach ($uomCodes as $uomCode) {
                $items[] = $uomCode->getItemId();
            }
        }
        $items = array_unique($items);
        foreach ($items as $item) {
            try {
                /** @var ReplBarcodeRepository $itemBarcodes */
                $itemBarcodes = $this->_getBarcode($item);
                /** @var ReplItemRepository $itemData */
                $itemData        = $this->_getItem($item);
                $productVariants = $this->getNewOrUpdatedProductVariants(-1, $item);
                if (!empty($itemData)) {
                    $totalUomCodes        = $this->replicationHelper->getUomCodes(
                        $itemData->getNavId(),
                        $this->getScopeId()
                    );
                    $uomCodesNotProcessed = $this->getNewOrUpdatedProductUoms(-1, $item);
                    if (count($totalUomCodes[$itemData->getNavId()]) > 1) {
                        if (!empty($productVariants) && empty($uomCodesNotProcessed)) {
                            $uomCodesNotProcessed = $this->getNewOrUpdatedProductUoms(
                                -1,
                                $item,
                                true
                            );
                        }
                        $productVariants = $this->getProductVariants($itemData->getNavId());
                    }
                    if (!empty($productVariants) || count($totalUomCodes[$itemData->getNavId()]) > 1) {
                        $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                            $item,
                            '',
                            '',
                            $this->store->getId()
                        );
                        $this->createConfigurableProducts(
                            $productData,
                            $itemData,
                            $itemBarcodes,
                            $productVariants,
                            $totalUomCodes,
                            $uomCodesNotProcessed
                        );
                    } else {
                        if (count($totalUomCodes[$itemData->getNavId()]) == 1) {
                            foreach ($uomCodesNotProcessed as $uomCode) {
                                $uomCode->addData(
                                    [
                                        'is_updated'   => 0,
                                        'processed_at' => $this->replicationHelper->getDateTime(),
                                        'processed'    => 1,
                                        'is_failed'    => 0
                                    ]
                                );

                                $this->replItemUomRepository->save($uomCode);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logDetailedException(
                    __METHOD__,
                    $this->store->getName(),
                    $item
                );
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Update/Add the modified/added standard variants of the item
     */
    public function updateStandardVariantsOnly()
    {
        $batchSize = $this->replicationHelper->getVariantBatchSize();
        $filters   = [
            ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
            ['field' => 'main_table.ready_to_process', 'value' => 1, 'condition_type' => 'eq']
        ];

        $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            $batchSize,
            1
        );
        $collection = $this->replItemVariantCollectionFactory->create();
        $this->replicationHelper->setCollectionForStandardVariants($collection, $criteria, true);

        $items = [];

        foreach ($collection as $variant) {
            $items[] = $variant->getItemId();
        }

        $items = array_unique($items);
        foreach ($items as $item) {
            try {
                $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                    $item,
                    '',
                    '',
                    $this->store->getId()
                );
                /** @var ReplBarcodeRepository $itemBarcodes */
                $itemBarcodes = $this->_getBarcode($item);
                /** @var ReplItemRepository $itemData */
                $itemData        = $this->_getItem($item);
                $productVariants = $this->getStandardProductVariants($item);
                if (!empty($itemData)) {
                    if (!empty($productVariants)) {
                        $this->createStandardConfigurableProducts(
                            $productData,
                            $itemData,
                            $itemBarcodes,
                            $productVariants
                        );
                    }
                }
            } catch (Exception $e) {
                $this->logDetailedException(
                    __METHOD__,
                    $this->store->getName(),
                    $item,
                );
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Cater Configurable Products Removal
     */
    public function caterItemsRemoval()
    {
        $filters = [
            ['field' => 'nav_id', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
        ];
        $items   = $this->getDeletedItemsOnly($filters);
        if (!empty($items->getItems())) {
            foreach ($items->getItems() as $value) {
                $sku = $value->getNavId();
                try {
                    $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                        $sku,
                        '',
                        '',
                        $this->store->getId()
                    );
                    $productData = $this->setProductStatus($productData, 1);
                    $this->productRepository->save($productData);
                } catch (Exception $e) {
                    $this->logDetailedException(
                        __METHOD__,
                        $this->store->getName(),
                        $sku
                    );
                    $this->logger->debug($e->getMessage());
                    $value->setData('is_failed', 1);
                }
                $value->setData('is_updated', 0);
                $value->setData('processed_at', $this->replicationHelper->getDateTime());
                $value->setData('processed', 1);
                // @codingStandardsIgnoreLine
                $this->itemRepository->save($value);
            }
        }
    }

    /**
     * Cater SimpleProducts Removal
     */
    public function caterVariantsRemoval()
    {
        $filters          = [
            ['field' => 'ItemId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],

        ];
        $variants         = $this->getDeletedVariantsOnly($filters);
        $parentProductIds = [];

        if (!empty($variants)) {
            /** @var ReplItemVariantRegistration $value */
            foreach ($variants as $value) {
                $itemId = $value->getItemId();
                try {
                    $productData             = $this->replicationHelper->getProductDataByIdentificationAttributes(
                        $itemId,
                        '',
                        '',
                        $this->store->getId()
                    );
                    $associatedSimpleProduct = $this->replicationHelper->getRelatedVariantGivenConfAttributesValues(
                        $productData,
                        $value,
                        $this->store->getId(),
                        true
                    );

                    foreach ($associatedSimpleProduct as $item) {
                        $item = $this->setProductStatus($item, 1);
                        // @codingStandardsIgnoreLine
                        $this->productRepository->save($item);
                    }

                    //Create an array of parent products to check saleable status
                    if (!in_array($productData->getId(), $parentProductIds)) {
                        $parentProductIds[] = $productData->getId();
                    }

                } catch (Exception $e) {
                    $this->logDetailedException(
                        __METHOD__,
                        $this->store->getName(),
                        $itemId
                    );
                    $this->logger->debug($e->getMessage());
                    $value->setData('is_failed', 1);
                }
                $value->setData('is_updated', 0);
                $value->setData('processed_at', $this->replicationHelper->getDateTime());
                $value->setData('processed', 1);
                // @codingStandardsIgnoreLine
                $this->replItemVariantRegistrationRepository->save($value);
            }

            //Disable configurable products if all associated products are disabled
            if (count($parentProductIds) > 0) {
                $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                    'entity_id',
                    $parentProductIds,
                    'in'
                )->create();
                $configProducts = $this->productRepository->getList($searchCriteria)->getItems();
                foreach ($configProducts as $configProduct) {
                    if (!$configProduct->isSalable()) {
                        $this->setProductStatus($configProduct, 1);
                        $this->productRepository->save($configProduct);
                    }
                }
            }
        }
    }

    /**
     * Cater SimpleProducts Removal for Uoms
     */
    public function caterUomsRemoval()
    {
        $filters = [
            ['field' => 'ItemId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],

        ];
        $uoms    = $this->getDeletedUomsOnly($filters);

        if (!empty($uoms)) {
            /** @var \Ls\Replication\Model\ReplItemUnitOfMeasure $uom */
            foreach ($uoms as $uom) {
                $itemId = $uom->getItemId();
                try {
                    $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                        $itemId,
                        '',
                        '',
                        'all'
                    );
                    if ($productData->getTypeId() == 'configurable') {
                        $children = $productData->getTypeInstance()->getUsedProducts($productData);
                        foreach ($children as $child) {
                            $childProductData = $this->productRepository->get($child->getSKU());
                            if ($childProductData->getData('uom') == $uom->getCode()) {
                                $childProductData = $this->setProductStatus($childProductData, 1);
                                // @codingStandardsIgnoreLine
                                $this->productRepository->save($childProductData);
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->logDetailedException(
                        __METHOD__,
                        $this->store->getName(),
                        $itemId
                    );
                    $this->logger->debug($e->getMessage());
                    $uom->setData('is_failed', 1);
                }
                $uom->setData('is_updated', 0);
                $uom->setData('IsDeleted', 0);
                $uom->setData('processed_at', $this->replicationHelper->getDateTime());
                $uom->setData('processed', 1);
                // @codingStandardsIgnoreLine
                $this->replItemUomRepository->save($uom);
            }
        }
    }

    /**
     * Update the modified/added barcode of the items & item variants
     */
    public function updateBarcodeOnly()
    {
        $itemId           = '';
        $cronProductCheck = $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_PRODUCT,
            ScopeInterface::SCOPE_STORES,
            $this->store->getId()
        );
        $barcodeBatchSize = $this->replicationHelper->getProductBarcodeBatchSize();
        if ($cronProductCheck == 1) {
            $criteria = $this->replicationHelper->buildCriteriaForNewItems(
                'scope_id',
                $this->getScopeId(),
                'eq',
                $barcodeBatchSize
            );
            /** @var ReplBarcodeSearchResults $replBarcodes */
            $replBarcodes = $this->replBarcodeRepository->getList($criteria);
            if ($replBarcodes->getTotalCount() > 0) {
                /** @var ReplBarcode $replBarcode */
                foreach ($replBarcodes->getItems() as $replBarcode) {
                    try {
                        $variantId = $uom = '';
                        $itemId    = $replBarcode->getItemId();
                        if ($replBarcode->getVariantId()) {
                            $variantId = $replBarcode->getVariantId();
                        }
                        if (!empty($replBarcode->getUnitOfMeasure())) {
                            $totalUomCodes = $this->replicationHelper->getUomCodes(
                                $replBarcode->getItemId(),
                                $this->getScopeId()
                            );

                            if (count($totalUomCodes[$replBarcode->getItemId()]) > 1) {
                                $uom = $replBarcode->getUnitOfMeasure();
                            }

                        }
                        $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                            $itemId,
                            $variantId,
                            $uom,
                            'global'
                        );
                        if (isset($productData)) {
                            $productData->setBarcode($replBarcode->getNavId());
                            // @codingStandardsIgnoreLine
                            $this->productResourceModel->saveAttribute($productData, 'barcode');
                        }
                    } catch (Exception $e) {
                        $this->logDetailedException(
                            __METHOD__,
                            $this->store->getName(),
                            $itemId,
                            $variantId,
                            $uom
                        );
                        $this->logger->debug($e->getMessage());
                        $replBarcode->setData('is_failed', 1);
                    }
                    $replBarcode->addData(
                        [
                            'is_updated'   => 0,
                            'processed_at' => $this->replicationHelper->getDateTime(),
                            'processed'    => 1
                        ]
                    );
                    $this->replBarcodeRepository->save($replBarcode);
                }
            }
        }
    }

    /**
     * Create standard configurable products
     *
     * @param $configProduct
     * @param $item
     * @param $itemBarcodes
     * @param $variants
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createStandardConfigurableProducts(
        $configProduct,
        $item,
        $itemBarcodes,
        $variants
    ) {
        $formattedCode  = $this->replicationHelper->formatAttributeCode(LSR::LS_STANDARD_VARIANT_ATTRIBUTE_CODE);
        $attributesCode = [$formattedCode];
        $this->attributeAssignmentToAttributeSet(
            $configProduct->getAttributeSetId(),
            $formattedCode,
            LSR::SC_REPLICATION_ATTRIBUTE_SET_VARIANTS_ATTRIBUTES_GROUP
        );

        $associatedProductIds = [];

        /** @var ReplItemVariant $value */
        foreach ($variants as $value) {
            $sku = $value->getItemId() . '-' . $value->getVariantId();
            try {
                $productData = $this->saveProductForWebsite($value->getItemId(), $value->getVariantId());
                try {
                    $name                   = $this->getNameForStandardVariant($value, $item);
                    $associatedProductIds[] = $this->updateStandardConfigProduct(
                        $productData,
                        $item,
                        $name,
                        $value
                    );
                    $associatedProductIds   = array_unique($associatedProductIds);
                } catch (Exception $e) {
                    $this->logDetailedException(
                        __METHOD__,
                        $this->store->getName(),
                        $value->getItemId(),
                        $value->getVariantId()
                    );
                    $this->logger->debug($e->getMessage());
                    $value->setData('is_failed', 1);
                }
            } catch (NoSuchEntityException $e) {
                $name      = $this->getNameForStandardVariant($value, $item);
                $productId = $this->createStandardConfigProduct(
                    $name,
                    $item,
                    $value,
                    $sku,
                    $configProduct,
                    $formattedCode,
                    $itemBarcodes
                );

                if ($productId) {
                    $associatedProductIds[] = $productId;
                } else {
                    $this->logger->debug(
                        sprintf(
                            'Variant issue : Item %s-%s option_id does not exists in attribute',
                            $value->getItemId(),
                            $value->getVariantId()
                        )
                    );
                    $value->setData('is_failed', 1);
                }
            }
            $value->setData('processed_at', $this->replicationHelper->getDateTime());
            $value->setData('processed', 1);
            $value->setData('is_updated', 0);
            $this->replItemVariantRepository->save($value);
        }

        $this->finalizeConfigurableProduct($configProduct, $attributesCode, $associatedProductIds);
    }

    /**
     * Convert main product into configurable and associate all the simple products
     *
     * @param $configProduct
     * @param $item
     * @param $itemBarcodes
     * @param $variants
     * @param null $totalUomCodes
     * @param null $uomCodesNotProcessed
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function createConfigurableProducts(
        $configProduct,
        $item,
        $itemBarcodes,
        $variants,
        $totalUomCodes = null,
        $uomCodesNotProcessed = null
    ) {
        // Get those attribute codes which are assigned to product.
        $attributesCode = $this->replicationHelper->_getAttributesCodes($item->getNavId(), $this->getScopeId());

        if (empty($attributesCode)) {
            $this->handleVariantsInCaseOfNoItemAttributeInExtendedVariant($variants);
            $variants = [];
        }

        foreach ($attributesCode as $attributeCode) {
            $this->attributeAssignmentToAttributeSet(
                $configProduct->getAttributeSetId(),
                $attributeCode,
                LSR::SC_REPLICATION_ATTRIBUTE_SET_VARIANTS_ATTRIBUTES_GROUP
            );
        }

        if (!empty($totalUomCodes)) {
            if (count($totalUomCodes[$item->getNavId()]) > 1 && !empty($uomCodesNotProcessed)) {
                $attributesCode [] = LSR::LS_UOM_ATTRIBUTE;
            } else {
                $uomCodesNotProcessed = null;
            }
        }

        if (empty($attributesCode)) {
            $this->handleUomCodesInCaseOfNoItemAttributeInExtendedVariant($uomCodesNotProcessed, $item->getNavId());
        }

        $attributesIds        = [];
        $associatedProductIds = [];
        if ($configProduct->getTypeId() == Configurable::TYPE_CODE) {
            $associatedProductIds = $configProduct->getTypeInstance()->getUsedProductIds($configProduct);
        }
        $configurableProductsData = [];
        if (!empty($uomCodesNotProcessed) && !empty($variants)) {
            /** @var \Ls\Replication\Model\ReplItemUnitOfMeasure $uomCode */
            foreach ($uomCodesNotProcessed as $uomCode) {
                /** @var ReplItemVariantRegistration $value */
                foreach ($variants as $value) {
                    try {
                        $uomDescription = $this->replicationHelper->getUomDescription($uomCode);
                        $sku            = $value->getItemId() . '-' . $value->getVariantId() . '-' . $uomCode->getCode();
                        $productData    = $this->saveProductForWebsite(
                            $value->getItemId(),
                            $value->getVariantId(),
                            $uomCode->getCode(),
                            $this->store->getId()
                        );
                        try {
                            $name                   = $this->getNameForVariant($value, $item);
                            $name                   = $this->getNameForUom($name, $uomDescription);
                            $associatedProductIds[] = $this->updateConfigProduct(
                                $productData,
                                $item,
                                $name,
                                $uomCode,
                                $value
                            );
                            $associatedProductIds   = array_unique($associatedProductIds);
                        } catch (Exception $e) {
                            $this->logDetailedException(
                                __METHOD__,
                                $this->store->getName(),
                                $value->getItemId(),
                                $value->getVariantId(),
                                $uomCode->getCode()
                            );
                            $this->logger->debug($e->getMessage());
                            $value->setData('is_failed', 1);
                        }
                    } catch (NoSuchEntityException $e) {
                        $isVariantContainNull = $this->validateVariant($attributesCode, $value);
                        if ($isVariantContainNull) {
                            $this->logger->debug(
                                sprintf(
                                    'Variant issue : Item %s-%s contain null attribute',
                                    $value->getItemId(),
                                    $value->getVariantId()
                                )
                            );
                            $value->setData('is_failed', 1);
                        } else {
                            $name      = $this->getNameForVariant($value, $item);
                            $name      = $this->getNameForUom($name, $uomDescription);
                            $productId = $this->createConfigProduct(
                                $name,
                                $item,
                                $value,
                                $uomCode,
                                $sku,
                                $configProduct,
                                $attributesCode,
                                $itemBarcodes
                            );

                            if ($productId) {
                                $associatedProductIds[] = $productId;
                            } else {
                                $this->logger->debug(
                                    sprintf(
                                        'Variant issue : Item %s-%s option_id does not exists in attribute',
                                        $value->getItemId(),
                                        $value->getVariantId()
                                    )
                                );
                                $value->setData('is_failed', 1);
                            }
                        }
                    }
                    $value->setData('processed_at', $this->replicationHelper->getDateTime());
                    $value->setData('processed', 1);
                    $value->setData('is_updated', 0);
                    $this->replItemVariantRegistrationRepository->save($value);
                }

                $uomCode->setData('processed_at', $this->replicationHelper->getDateTime());
                $uomCode->setData('processed', 1);
                $uomCode->setData('is_updated', 0);
                $this->replItemUomRepository->save($uomCode);
            }
        } elseif (!empty($uomCodesNotProcessed) && empty($variants)) {
            /** @var \Ls\Replication\Model\ReplItemUnitOfMeasure $uomCode */
            foreach ($uomCodesNotProcessed as $uomCode) {
                $uomDescription = $this->replicationHelper->getUomDescription($uomCode);
                $value          = null;
                $sku            = $uomCode->getItemId() . '-' . $uomCode->getCode();
                $name           = $this->getNameForUom($item->getDescription(), $uomDescription);
                try {
                    $productData = $this->saveProductForWebsite(
                        $uomCode->getItemId(),
                        '',
                        $uomCode->getCode(),
                        $this->store->getId()
                    );
                    try {
                        $associatedProductIds[] = $this->updateConfigProduct($productData, $item, $name, $uomCode);
                        $associatedProductIds   = array_unique($associatedProductIds);
                    } catch (Exception $e) {
                        $this->logDetailedException(
                            __METHOD__,
                            $this->store->getName(),
                            $uomCode->getItemId(),
                            null,
                            $uomCode->getCode()
                        );
                        $this->logger->debug($e->getMessage());
                        $uomCode->setData('is_failed', 1);
                    }
                } catch (NoSuchEntityException $e) {
                    $productId = $this->createConfigProduct(
                        $name,
                        $item,
                        $value,
                        $uomCode,
                        $sku,
                        $configProduct,
                        $attributesCode,
                        $itemBarcodes
                    );

                    if ($productId) {
                        $associatedProductIds[] = $productId;
                    } else {
                        $this->logger->debug(
                            sprintf(
                                'Variant issue : Item %s-%s option_id does not exists in attribute',
                                $uomCode->getItemId(),
                                $uomCode->getCode()
                            )
                        );
                        $uomCode->setData('is_failed', 1);
                    }
                }
                $uomCode->setData('processed_at', $this->replicationHelper->getDateTime());
                $uomCode->setData('processed', 1);
                $uomCode->setData('is_updated', 0);
                $this->replItemUomRepository->save($uomCode);
            }
        } else {
            /** @var ReplItemVariantRegistration $value */
            foreach ($variants as $value) {
                $sku     = $value->getItemId() . '-' . $value->getVariantId();
                $uomCode = null;
                try {
                    $productData = $this->saveProductForWebsite($value->getItemId(), $value->getVariantId());
                    try {
                        $name                   = $this->getNameForVariant($value, $item);
                        $associatedProductIds[] = $this->updateConfigProduct(
                            $productData,
                            $item,
                            $name,
                            $uomCode,
                            $value
                        );
                        $associatedProductIds   = array_unique($associatedProductIds);
                    } catch (Exception $e) {
                        $this->logDetailedException(
                            __METHOD__,
                            $this->store->getName(),
                            $value->getItemId(),
                            $value->getVariantId()
                        );
                        $this->logger->debug($e->getMessage());
                        $value->setData('is_failed', 1);
                    }
                } catch (NoSuchEntityException $e) {
                    $isVariantContainNull = $this->validateVariant($attributesCode, $value);
                    if ($isVariantContainNull) {
                        $this->logger->debug(
                            sprintf(
                                'Variant issue : Item %s-%s contain null attribute',
                                $value->getItemId(),
                                $value->getVariantId()
                            )
                        );
                        $value->setData('is_failed', 1);
                    } else {
                        $name      = $this->getNameForVariant($value, $item);
                        $productId = $this->createConfigProduct(
                            $name,
                            $item,
                            $value,
                            $uomCode,
                            $sku,
                            $configProduct,
                            $attributesCode,
                            $itemBarcodes
                        );

                        if ($productId) {
                            $associatedProductIds[] = $productId;
                        } else {
                            $this->logger->debug(
                                sprintf(
                                    'Variant issue : Item %s-%s option_id does not exists in attribute',
                                    $value->getItemId(),
                                    $value->getVariantId()
                                )
                            );
                            $value->setData('is_failed', 1);
                        }
                    }
                }
                $value->setData('processed_at', $this->replicationHelper->getDateTime());
                $value->setData('processed', 1);
                $value->setData('is_updated', 0);
                $this->replItemVariantRegistrationRepository->save($value);
            }
        }

        $this->finalizeConfigurableProduct($configProduct, $attributesCode, $associatedProductIds);
    }

    /**
     * Finalize configurable product
     *
     * @param $configProduct
     * @param $attributesCode
     * @param $associatedProductIds
     * @return void
     * @throws LocalizedException
     */
    public function finalizeConfigurableProduct(
        $configProduct,
        $attributesCode,
        $associatedProductIds
    ) {
        // This is added to take care Magento Commerce PK
        $productId = $configProduct->getDataByKey('row_id');
        if (empty($productId)) {
            $productId = $configProduct->getId();
        }
        $position          = 0;
        $attributeData     = [];
        $attributeIdsArray = $this->validateConfigurableAttributes($configProduct);
        $attributesIds     = [];
        foreach ($attributesCode as $value) {
            /** @var Interceptor $attribute */
            $attribute       = $this->eavConfig->getAttribute('catalog_product', $value);
            $attributesIds[] = $attribute->getId();
            $data            = [
                'attribute_id' => $attribute->getId(),
                'product_id'   => $productId,
                'position'     => $position
            ];

            $attributeData[] = $this->getConfigurableAttributeData($attribute, $position);
            try {
                if (!in_array($attribute->getId(), $attributeIdsArray)) {
                    // @codingStandardsIgnoreLine
                    $this->attribute->setData($data)->save();
                }
            } catch (Exception $e) {
                // @codingStandardsIgnoreLine
                $this->logger->debug(sprintf('Issue while saving Attribute Id : %s and Product Id : %s - %s',
                    $attribute->getId(), $productId, $e->getMessage()));
            }
            $position++;
        }
        $options = $this->optionsFactory->create($attributeData);
        $configProduct->getExtensionAttributes()->setConfigurableProductOptions($options);
        $configProduct->setTypeId(Configurable::TYPE_CODE); // Setting Product Type As Configurable
        $configProduct->setAffectConfigurableProductAttributes($configProduct->getAttributeSetId());
        $this->configurable->setUsedProductAttributes($configProduct, $attributesIds);
        $configProduct->setNewVariationsAttributeSetId($configProduct->getAttributeSetId()); // Setting Attribute Set Id
        $configProduct->setConfigurableProductsData([]);
        $configProduct->setCanSaveConfigurableAttributes(true);
        $configProduct->setAssociatedProductIds($associatedProductIds); // Setting Associated Products

        if ($stockItem = $configProduct->getExtensionAttributes()->getStockItem()) {
            $stockItem->setIsInStock(1)->setStockStatusChangedAutomaticallyFlag(1);

            $itemStock = $this->replicationHelper->getInventoryStatus(
                $configProduct->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE),
                $this->webStoreId,
                $this->getScopeId()
            );
            if ($itemStock) {
                if (fmod((float)$itemStock->getQuantity(), 1) != 0) {
                    $stockItem
                        ->setIsQtyDecimal(1)
                        ->setUseConfigMinSaleQty(0)
                        ->setMinSaleQty(0.1);
                }
            }
        }
        try {
            $this->productRepository->save($configProduct);
        } catch (Exception $e) {
            $this->logger->debug(
                sprintf(
                    'Exception while saving Configurable Product Id : %s - %s',
                    $productId,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Handle crappy data when zero relevant attribute is available in extended variants
     *
     * @param $variants
     */
    public function handleVariantsInCaseOfNoItemAttributeInExtendedVariant($variants)
    {
        foreach ($variants as $value) {
            $this->logger->debug(
                sprintf(
                    'Variant issue : Item %s has zero attribute available in extended variants',
                    $value->getItemId()
                )
            );
            $value->addData(
                [
                    'is_updated'   => 0,
                    'processed_at' => $this->replicationHelper->getDateTime(),
                    'processed'    => 1,
                    'is_failed'    => 1
                ]
            );
            $this->replItemVariantRegistrationRepository->save($value);
        }
    }

    /**
     * Handle crappy data when zero uom attribute is available for creating configurable product
     *
     * @param $uomCodesNotProcessed
     * @param $itemId
     * @return mixed
     * @throws Exception
     */
    public function handleUomCodesInCaseOfNoItemAttributeInExtendedVariant($uomCodesNotProcessed, $itemId)
    {
        if ($uomCodesNotProcessed) {
            foreach ($uomCodesNotProcessed as $uomCode) {
                $this->logger->debug(
                    sprintf(
                        'Variant issue : Item %s has zero uom attribute available',
                        $uomCode->getItemId()
                    )
                );
                $uomCode->addData(
                    [
                        'is_updated'   => 0,
                        'processed_at' => $this->replicationHelper->getDateTime(),
                        'processed'    => 1,
                        'is_failed'    => 1
                    ]
                );
                $this->replItemUomRepository->save($uomCode);
            }
        }
        // phpcs:ignore Magento2.Exceptions.DirectThrow
        throw new \Exception(sprintf('Could not create any variant for item %s due to crappy data', $itemId));
    }

    /**
     * Formulate name for the variant
     *
     * @param $value
     * @param $item
     * @return string
     */
    public function getNameForVariant(
        ReplItemVariantRegistration $value,
        ReplItem $item
    ) {
        $d1 = (($value->getVariantDimension1()) ?: '');
        $d2 = (($value->getVariantDimension2()) ?: '');
        $d3 = (($value->getVariantDimension3()) ?: '');
        $d4 = (($value->getVariantDimension4()) ?: '');
        $d5 = (($value->getVariantDimension5()) ?: '');
        $d6 = (($value->getVariantDimension6()) ?: '');

        /** @var ProductInterface $productV */
        $dMerged = (($d1) ? '-' . $d1 : '') . (($d2) ? '-' . $d2 : '') . (($d3) ? '-' . $d3 : '') .
            (($d4) ? '-' . $d4 : '') . (($d5) ? '-' . $d5 : '') . (($d6) ? '-' . $d6 : '');
        $name    = $item->getDescription() . $dMerged;
        return $name;
    }

    /**
     * @param ReplItemVariant $value
     * @param ReplItem $item
     * @return string
     */
    public function getNameForStandardVariant(
        ReplItemVariant $value,
        ReplItem $item
    ) {
        $d1      = (($value->getDescription2()) ?: '');
        $dMerged = (($d1) ? '-' . $d1 : '');

        return $item->getDescription() . $dMerged;
    }

    /**
     * Formulate name on the basis of uom
     *
     * @param $name
     * @param $description
     * @return string
     */
    public function getNameForUom($name, $description)
    {
        $name = $name . ' ' . $description;
        return $name;
    }

    /**
     * Setting website id for the item
     *
     * @param string $itemId
     * @param string $variantId
     * @param string $uomCode
     * @param string $storeId
     * @return mixed|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function saveProductForWebsite($itemId, $variantId = '', $uomCode = '', $storeId = '')
    {
        $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
            $itemId,
            $variantId,
            $uomCode,
            'global'
        );

        $websitesProduct = $productData->getWebsiteIds();
        /** Check if Item exist in the website and assign it if it does not exist*/
        if (!in_array($this->store->getWebsiteId(), $websitesProduct)) {
            $websitesProduct[] = $this->store->getWebsiteId();
            $productData->setWebsiteIds($websitesProduct)
                ->save();
        }

        return $productData;
    }

    /**
     * Update config product
     *
     * @param $productData
     * @param $item
     * @param $name
     * @param null $uomCode
     * @param null $value
     * @return int|null
     * @throws LocalizedException
     */
    public function updateConfigProduct($productData, $item, $name, $uomCode = null, $value = null)
    {
        $productStatus = true;
        $productData->setName($name);
        $productData->setMetaTitle($name);
        $productData->setDescription($item->getDetails());
        $productData->setWeight($item->getGrossWeight());
        $productData->setCustomAttribute(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $item->getNavId());
        if (!empty($item->getTaxItemGroupId())) {
            $taxClass = $this->replicationHelper->getTaxClassGivenName(
                $item->getTaxItemGroupId()
            );

            if (!empty($taxClass)) {
                $productData->setTaxClassId($taxClass->getClassId());
            }
        }
        if (!empty($uomCode)) {
            $uomDescription = $this->replicationHelper->getUomDescription($uomCode);
            $this->syncUomAdditionalAttributes($productData, $uomCode, $item);
            $optionId = $this->replicationHelper->_getOptionIDByCode(
                LSR::LS_UOM_ATTRIBUTE,
                $uomDescription
            );
            $productData->setData(LSR::LS_UOM_ATTRIBUTE, $optionId);
            //Set blocked on eCommerce for unit of measure product
            if ($uomCode->getEComSelection() == 1) {
                $productData   = $this->setProductStatus($productData, $uomCode->getEComSelection());
                $productStatus = false;
            }
        } else {
            if (($item->getBaseUnitOfMeasure() != $item->getSalseUnitOfMeasure()) &&
                !empty($item->getSalseUnitOfMeasure())) {
                $productData->setCustomAttribute('uom', $item->getSalseUnitOfMeasure());
            } else {
                $productData->setCustomAttribute('uom', $item->getBaseUnitOfMeasure());
            }
        }

        if ($value) {
            $productData->setCustomAttribute(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE, $value->getVariantId());
            //Set variant status as per BlockedOnEcom status of each variant
            if ($value->getBlockedOnECom() == 1) {
                $productData   = $this->setProductStatus($productData, $value->getBlockedOnECom());
                $productStatus = false;
            }
        }

        if ($productStatus) {
            $productData = $this->setProductStatus($productData, 0);
        }

        $this->resetSpecificItemPriceStatus($item, $value, $uomCode);

        try {
            // @codingStandardsIgnoreLine
            $productSaved = $this->productRepository->save($productData);
            return $productSaved->getId();
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Set UOM Attributes to product
     * @param $product
     * @param $uomCode
     * @param $item
     * @return void
     */
    public function syncUomAdditionalAttributes($product, $uomCode, $item)
    {
        $product->setCustomAttribute('uom', $uomCode->getCode());
        $product->setCustomAttribute(LSR::LS_UOM_ATTRIBUTE_QTY, $uomCode->getQtyPrUOM());
        $product->setCustomAttribute(LSR::LS_UOM_ATTRIBUTE_HEIGHT, $uomCode->getHeight());
        $weight = ($uomCode->getWeight() != "0" && $uomCode->getWeight()) ?
            $uomCode->getWeight() : $item->getGrossWeight();
        $product->setWeight($weight);
        $product->setCustomAttribute(LSR::LS_UOM_ATTRIBUTE_LENGTH, $uomCode->getLength());
        $product->setCustomAttribute(LSR::LS_UOM_ATTRIBUTE_WIDTH, $uomCode->getWidth());
        $product->setCustomAttribute(LSR::LS_UOM_ATTRIBUTE_CUBAGE, $uomCode->getCubage());
    }

    /**
     * Update standard configurable product
     *
     * @param $productData
     * @param $item
     * @param $name
     * @param $value
     * @return int|void|null
     */
    public function updateStandardConfigProduct($productData, $item, $name, $value = null)
    {
        $productData->setStoreId($this->store->getId());
        $productData->setName($name);
        $productData->setMetaTitle($name);
        $productData->setDescription($item->getDetails());
        $productData->setWeight($item->getGrossWeight());
        $productData->setCustomAttribute(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $item->getNavId());
        if (($item->getBaseUnitOfMeasure() != $item->getSalseUnitOfMeasure())
            && !empty($item->getSalseUnitOfMeasure())) {
            $productData->setCustomAttribute('uom', $item->getSalseUnitOfMeasure());
        } else {
            $productData->setCustomAttribute('uom', $item->getBaseUnitOfMeasure());
        }

        if ($value) {
            $productData->setCustomAttribute(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE, $value->getVariantId());
        }

        try {
            // @codingStandardsIgnoreLine
            $productSaved = $this->productRepository->save($productData);
            return $productSaved->getId();
        } catch (Exception $e) {
            $this->logDetailedException(
                __METHOD__,
                $this->store->getName(),
                $item->getNavId(),
                $value ? $value->getVariantId() : null
            );
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Create standard configurable product
     *
     * @param $name
     * @param $item
     * @param $value
     * @param $sku
     * @param $configProduct
     * @param $attributesCode
     * @param $itemBarcodes
     * @return int|void|null
     * @throws LocalizedException
     */
    public function createStandardConfigProduct(
        $name,
        $item,
        $value,
        $sku,
        $configProduct,
        $attributesCode,
        $itemBarcodes
    ) {
        $productV = $this->productFactory->create();
        $productV->setName($name);
        $productV->setStoreId($this->store->getId());
        $productV->setWebsiteIds([$this->store->getWebsiteId()]);
        $productV->setMetaTitle($name);
        $productV->setDescription($item->getDetails());
        $productV->setSku($sku);
        $productV->setWeight($item->getGrossWeight());
        $unitOfMeasure = null;
        $itemPrice     = $this->getItemPrice($value->getItemId(), $value->getVariantId(), $unitOfMeasure);
        if (isset($itemPrice)) {
            $productV->setPrice($itemPrice->getUnitPriceInclVat());
        } else {
            // Just in-case if we don't have price for Variant then in that case,
            // we are using the price of main product.
            $price = $this->getItemPrice($item->getNavId());
            if (!empty($price)) {
                $productV->setPrice($price->getUnitPriceInclVat());
            } else {
                $productV->setPrice($item->getUnitPrice());
            }
        }
        $productV->setAttributeSetId($configProduct->getAttributeSetId());
        $productV->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);

        $productV->setTypeId(Type::TYPE_SIMPLE);
        $productV->setCustomAttribute(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $item->getNavId());
        $variantDimension1 = $value->getDescription2();

        $d1       = (($variantDimension1 != '' && $variantDimension1 != null) ? $variantDimension1 : '');
        $optionId = $this->replicationHelper->_getOptionIDByCode(
            $attributesCode,
            $d1
        );
        if (isset($optionId)) {
            $productV->setData($attributesCode, $optionId);
        } else {
            return null;
        }

        $itemStock = $this->replicationHelper->getInventoryStatus(
            $value->getItemId(),
            $this->webStoreId,
            $this->getScopeId(),
            $value->getVariantId()
        );

        if (!$itemStock) {
            $itemStock = $this->replicationHelper->getInventoryStatus(
                $item->getNavId(),
                $this->webStoreId,
                $this->getScopeId()
            );
        }
        $productV = $this->replicationHelper->manageStock($productV, $item->getType());
        if ($value->getVariantId()) {
            $productV->setCustomAttribute(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE, $value->getVariantId());
        }
        if (($item->getBaseUnitOfMeasure() != $item->getSalseUnitOfMeasure())
            && !empty($item->getSalseUnitOfMeasure())) {
            $productV->setCustomAttribute('uom', $item->getSalseUnitOfMeasure());
        } else {
            $productV->setCustomAttribute('uom', $item->getBaseUnitOfMeasure());
        }
        if (isset($itemBarcodes[$sku])) {
            $productV->setCustomAttribute('barcode', $itemBarcodes[$sku]);
        }
        try {
            // @codingStandardsIgnoreStart
            $productSaved = $this->productRepository->save($productV);

            if ($itemStock) {
                $this->replicationHelper->updateInventory($productSaved, $itemStock);
            }

            return $productSaved->getId();
            // @codingStandardsIgnoreEnd
        } catch (Exception $e) {
            $this->logDetailedException(
                __METHOD__,
                $this->store->getName(),
                $item->getNavId(),
                $value->getVariantId(),
                $unitOfMeasure
            );
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Reset specific product price so that it gets updated next time sync_price cron runs
     *
     * @param $item
     * @param $value
     * @param $uomCode
     * @return mixed|null
     */
    public function resetSpecificItemPriceStatus($item, $value, $uomCode)
    {
        $unitOfMeasure = null;

        if (!empty($uomCode)) {
            if ($uomCode->getCode() != $item->getBaseUnitOfMeasure()) {
                $unitOfMeasure = $uomCode->getCode();
            }
        }
        if (isset($uomCode) && isset($value)) {
            $itemPrice = $this->getItemPrice($value->getItemId(), $value->getVariantId(), $unitOfMeasure, 0);
        } elseif (isset($uomCode)) {
            $itemPrice = $this->getItemPrice($uomCode->getItemId(), null, $unitOfMeasure, 0);
        } else {
            $itemPrice = $this->getItemPrice($value->getItemId(), $value->getVariantId(), $unitOfMeasure, 0);
        }
        if (!isset($itemPrice)) {
            $itemPrice = $this->getItemPrice($item->getNavId(), null, null, 0);
        }

        return $itemPrice;
    }

    /**
     * Create associated simple product given data
     *
     * @param $name
     * @param $item
     * @param $value
     * @param $uomCode
     * @param $sku
     * @param $configProduct
     * @param $attributesCode
     * @param $itemBarcodes
     * @return int|null
     * @throws LocalizedException
     */
    private function createConfigProduct(
        $name,
        $item,
        $value,
        $uomCode,
        $sku,
        $configProduct,
        $attributesCode,
        $itemBarcodes
    ) {
        $productStatus = true;
        $productV      = $this->productFactory->create();
        $productV->setName($name);
        $productV->setStoreId(0);
        $productV->setWebsiteIds([$this->store->getWebsiteId()]);
        $productV->setMetaTitle($name);
        $productV->setDescription($item->getDetails());
        $productV->setSku($sku);
        $productV->setWeight($item->getGrossWeight());
        if (!empty($item->getTaxItemGroupId())) {
            $taxClass = $this->replicationHelper->getTaxClassGivenName(
                $item->getTaxItemGroupId()
            );

            if (!empty($taxClass)) {
                $productV->setTaxClassId($taxClass->getClassId());
            }
        }
        $unitOfMeasure = null;
        if (!empty($uomCode)) {
            if ($uomCode->getCode() != $item->getBaseUnitOfMeasure()) {
                $unitOfMeasure = $uomCode->getCode();
            }
        }
        if (isset($uomCode) && isset($value)) {
            $itemPrice = $this->getItemPrice($value->getItemId(), $value->getVariantId(), $unitOfMeasure);
            $this->syncImagesForUom($value->getItemId(), $value->getVariantId(), $productV);
        } elseif (isset($uomCode)) {
            $itemPrice = $this->getItemPrice($uomCode->getItemId(), null, $unitOfMeasure);
            $this->syncImagesForUom($uomCode->getItemId(), '', $productV);
        } else {
            $itemPrice = $this->getItemPrice($value->getItemId(), $value->getVariantId(), $unitOfMeasure);
        }
        if (isset($itemPrice)) {
            $productV->setPrice($itemPrice->getUnitPriceInclVat());
        } else {
            // Just in-case if we don't have price for Variant then in that case,
            // we are using the price of main product.
            $price = $this->getItemPrice($item->getNavId());
            if (!empty($price)) {
                $productV->setPrice($price->getUnitPriceInclVat());
            } else {
                $productV->setPrice($item->getUnitPrice());
            }
        }
        $productV->setAttributeSetId($configProduct->getAttributeSetId());
        $productV->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);

        //Set variant status as per BlockedOnEcom status of each variant
        if ($value && $value->getBlockedOnECom() == 1) {
            $productV      = $this->setProductStatus($productV, $value->getBlockedOnECom());
            $productStatus = false;
        }

        //Set blocked on eCommerce for unit of measure product
        if ($uomCode && $uomCode->getEComSelection() == 1) {
            $productV      = $this->setProductStatus($productV, $uomCode->getEComSelection());
            $productStatus = false;
        }

        if ($productStatus) {
            $productV = $this->setProductStatus($productV, 0);
        }

        $productV->setTypeId(Type::TYPE_SIMPLE);
        $productV->setCustomAttribute(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $item->getNavId());
        if ($value) {
            $variantDimension1 = $value->getVariantDimension1();
            $variantDimension2 = $value->getVariantDimension2();
            $variantDimension3 = $value->getVariantDimension3();
            $variantDimension4 = $value->getVariantDimension4();
            $variantDimension5 = $value->getVariantDimension5();
            $variantDimension6 = $value->getVariantDimension6();

            $d1 = (($variantDimension1 != '' && $variantDimension1 != null) ? $variantDimension1 : '');
            $d2 = (($variantDimension2 != '' && $variantDimension2 != null) ? $variantDimension2 : '');
            $d3 = (($variantDimension3 != '' && $variantDimension3 != null) ? $variantDimension3 : '');
            $d4 = (($variantDimension4 != '' && $variantDimension4 != null) ? $variantDimension4 : '');
            $d5 = (($variantDimension5 != '' && $variantDimension5 != null) ? $variantDimension5 : '');
            $d6 = (($variantDimension6 != '' && $variantDimension6 != null) ? $variantDimension6 : '');
        }
        foreach ($attributesCode as $keyCode => $valueCode) {
            if ($valueCode == LSR::LS_UOM_ATTRIBUTE) {
                $uomDescription = $this->replicationHelper->getUomDescription($uomCode);
                $optionValue    = $uomDescription;
            } else {
                $optionValue = ${'d' . $keyCode};
            }
            if ((isset($keyCode) && $keyCode != '') || $keyCode == 0) {
                $optionId = $this->replicationHelper->_getOptionIDByCode(
                    $valueCode,
                    $optionValue
                );
                if (isset($optionId)) {
                    $productV->setData($valueCode, $optionId);
                } else {
                    return null;
                }
            }
        }
        if ($uomCode) {
            $this->syncUomAdditionalAttributes($productV, $uomCode, $item);
        } else {
            if (($item->getBaseUnitOfMeasure() != $item->getSalseUnitOfMeasure())
                && !empty($item->getSalseUnitOfMeasure())) {
                $productV->setCustomAttribute('uom', $item->getSalseUnitOfMeasure());
            } else {
                $productV->setCustomAttribute('uom', $item->getBaseUnitOfMeasure());
            }
        }
        if (isset($itemBarcodes[$sku])) {
            $productV->setCustomAttribute('barcode', $itemBarcodes[$sku]);
        }
        if ($value) {
            $itemStock = $this->replicationHelper->getInventoryStatus(
                $value->getItemId(),
                $this->webStoreId,
                $this->getScopeId(),
                $value->getVariantId()
            );
            if ($value->getVariantId()) {
                $productV->setCustomAttribute(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE, $value->getVariantId());
            }
        } else {
            $itemStock = $this->replicationHelper->getInventoryStatus(
                $item->getNavId(),
                $this->webStoreId,
                $this->getScopeId()
            );
        }
        $productV = $this->replicationHelper->manageStock($productV, $item->getType());
        try {
            /** @var ProductInterface $productSaved */
            // @codingStandardsIgnoreStart
            $productSaved = $this->productRepository->save($productV);

            if ($itemStock) {
                $this->replicationHelper->updateInventory($productSaved, $itemStock);
            }

            return $productSaved->getId();
            // @codingStandardsIgnoreEnd
        } catch (Exception $e) {
            $this->logDetailedException(
                __METHOD__,
                $this->store->getName(),
                $item->getNavId(),
                $value ? $value->getVariantId() : null,
                $uomCode
            );
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Validate data for the simple product
     *
     * @param $attributesCode
     * @param $value
     * @return bool
     */
    private function validateVariant($attributesCode, $value)
    {
        $isVariantContainNull = false;
        $variantDimension1    = $value->getVariantDimension1();
        $variantDimension2    = $value->getVariantDimension2();
        $variantDimension3    = $value->getVariantDimension3();
        $variantDimension4    = $value->getVariantDimension4();
        $variantDimension5    = $value->getVariantDimension5();
        $variantDimension6    = $value->getVariantDimension6();

        $d1 = (($variantDimension1 != '' && $variantDimension1 != null) ? $variantDimension1 : '');
        $d2 = (($variantDimension2 != '' && $variantDimension2 != null) ? $variantDimension2 : '');
        $d3 = (($variantDimension3 != '' && $variantDimension3 != null) ? $variantDimension3 : '');
        $d4 = (($variantDimension4 != '' && $variantDimension4 != null) ? $variantDimension4 : '');
        $d5 = (($variantDimension5 != '' && $variantDimension5 != null) ? $variantDimension5 : '');
        $d6 = (($variantDimension6 != '' && $variantDimension6 != null) ? $variantDimension6 : '');

        /** Check if all configurable attributes have value or not. */
        foreach ($attributesCode as $keyCode => $valueCode) {
            if (${'d' . $keyCode} == '' && $valueCode != LSR::LS_UOM_ATTRIBUTE) {
                // @codingStandardsIgnoreLine
                // Validation failed, that attribute contain some crappy data or null attribute which we does not need to process
                $isVariantContainNull = true;
                break;
            }
        }

        return $isVariantContainNull;
    }

    /**
     * Getting remaining records
     *
     * @param $storeData
     * @return int
     */
    public function getRemainingRecords($storeData)
    {
        if (!$this->remainingRecords) {
            $criteria               = $this->replicationHelper->buildCriteriaForNewItems(
                'scope_id',
                $this->getScopeId(),
                'eq',
                -1
            );
            $this->remainingRecords = $this->itemRepository->getList($criteria)->getTotalCount();
        }
        return $this->remainingRecords;
    }

    /**
     * Getting configurable attribute data
     *
     * @param $attribute
     * @param $position
     * @return array
     */
    public function getConfigurableAttributeData($attribute, $position)
    {
        $attributeValues = [];
        foreach ($attribute->getOptions() as $option) {
            if ($option->getValue()) {
                $attributeValues[] = [
                    'label'        => $option->getLabel(),
                    'attribute_id' => $attribute->getId(),
                    'value_index'  => $option->getValue(),
                ];
            }
        }
        return [
            'position'     => $position,
            'attribute_id' => $attribute->getId(),
            'label'        => $attribute->getName(),
            'values'       => $attributeValues
        ];
    }

    /**
     * Setting product status as enable/disable
     *
     * @param $product
     * @param $disableStatus
     * @return mixed
     */
    public function setProductStatus($product, $disableStatus)
    {
        if ($disableStatus == 1) {
            $product->setStatus(Status::STATUS_DISABLED);
        } else {
            $product->setStatus(Status::STATUS_ENABLED);
        }

        return $product;
    }

    /**
     * Updating the product status on global level
     *
     * @param $product
     */
    public function updateProductStatusGlobal($product)
    {
        try {
            $product->setStoreId(0);
            $product->getResource()->saveAttribute($product, 'status');
        } catch (Exception $e) {
            $this->logDetailedException(
                __METHOD__,
                $this->store->getName(),
                $product->getItemId(),
                $product->getVariantId()
            );
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Get attribute group
     *
     * @param $name
     * @param $attributeSetId
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getAttributeGroup($name, $attributeSetId)
    {
        $criteria = $this->searchCriteriaBuilder->setFilterGroups([]);
        $criteria->addFilter('attribute_group_name', $name, 'eq');
        $criteria->addFilter('attribute_set_id', $attributeSetId, 'eq');
        $criteria->setPageSize(1)->setCurrentPage(1);
        $searchCriteria = $criteria->create();

        $result = $this->attributeGroupRepository->getList($searchCriteria);
        if ($result->getTotalCount()) {
            $items = $result->getItems();
            return reset($items)->getAttributeGroupId();
        }
        return null;
    }

    /**
     * Check if attribute already exists in the attribute set
     *
     * @param $attributeSetId
     * @param $attributeCode
     * @return bool
     */
    public function checkAttributeInAttributeSet($attributeSetId, $attributeCode)
    {
        $collection = $this->eavAttributeCollectionFactory->create();
        $collection->setAttributeSetFilter($attributeSetId);
        $collection->addFieldToFilter(
            'attribute_code',
            $attributeCode
        );

        return $collection->getSize() === 0;
    }

    /**
     * Get attribute sort order in attribute set
     *
     * @param $attributeSetId
     * @param $attributeGroupId
     * @return bool
     */
    public function getAttributeSortOrderInAttributeSet($attributeSetId, $attributeGroupId)
    {
        $collection = $this->eavAttributeCollectionFactory->create();
        $collection->setAttributeSetFilter($attributeSetId);
        $collection->addFieldToFilter(
            'attribute_group_id',
            $attributeGroupId
        );
        $collection->setOrder('entity_attribute.sort_order', SortOrder::SORT_DESC);
        if ($collection->getSize() > 0) {
            $items = $collection->getItems();
            return reset($items)->getSortOrder() + 1;
        }
    }

    /**
     * Validating configurable attributes
     *
     * @param $configProduct
     * @return array|null
     */
    public function validateConfigurableAttributes($configProduct)
    {
        $attributeIds        = [];
        $extensionAttributes = $configProduct->getExtensionAttributes();
        if ($extensionAttributes === null) {
            return $attributeIds;
        }
        $configurableOptions = (array)$extensionAttributes->getConfigurableProductOptions();

        /** @var OptionInterface $configurableOption */
        foreach ($configurableOptions as $configurableOption) {
            $attributeIds[] = $configurableOption->getAttributeId();
        }
        return $attributeIds;
    }

    /**
     * Syncing image for uom
     *
     * @param $itemId
     * @param $variantId
     * @param $uomProduct
     */
    public function syncImagesForUom($itemId, $variantId, $uomProduct)
    {
        try {
            try {
                $product = $this->replicationHelper->getProductDataByIdentificationAttributes($itemId, $variantId);
            } catch (NoSuchEntityException $e) {
                return;
            }
            //Assign image from the already created product to the uom product
            $images = $product->getMediaGalleryImages();
            foreach ($images as $image) {
                if ($path = $image->getPath()) {
                    $uomProduct->addImageToMediaGallery(
                        $path,
                        ['image', 'thumbnail', 'small_image'],
                        false,
                        false
                    );
                }
            }
        } catch (Exception $e) {
            $this->logDetailedException(
                __METHOD__,
                $this->store->getName(),
                $itemId,
                $variantId
            );
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Attribute assignment to attribute set and handle attribute set group
     *
     * @param $attributeSetId
     * @param $formattedCode
     * @param $groupName
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function attributeAssignmentToAttributeSet($attributeSetId, $formattedCode, $groupName)
    {
        if ($this->checkAttributeInAttributeSet($attributeSetId, $formattedCode)) {
            $attributeGroupId = $this->getAttributeGroup(
                $groupName,
                $attributeSetId
            );
            if ($attributeGroupId == null) {
                $attributeGroup = $this->createAttributeGroup($attributeSetId, $groupName);
                if (!empty($attributeGroup)) {
                    $attributeGroupId = $attributeGroup->getId();
                }
            }
            $sortOrder = $this->getAttributeSortOrderInAttributeSet(
                $attributeSetId,
                $attributeGroupId
            );
            $this->assignAttributeToAttributeSet(
                $attributeSetId,
                $attributeGroupId,
                $formattedCode,
                $sortOrder
            );
            $this->eavConfig->clear(); //Clearing attribute set cache in case if new attribute has been added
        }
    }

    /**
     * Creating attribute group
     *
     * @param $attributeSetId
     * @param $groupName
     * @return AttributeGroupInterface|null
     */
    public function createAttributeGroup($attributeSetId, $groupName)
    {
        $attributesGroup = null;
        try {
            $attributeGroup = $this->attributeSetGroupFactory->create();
            $attributeGroup->setAttributeSetId($attributeSetId);
            $attributeGroup->setAttributeGroupName($groupName);
            $attributesGroup = $this->attributeGroupRepository->save($attributeGroup);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $attributesGroup;
    }

    /**
     * assign attribute to attribute set
     *
     * @param $attributeSetId
     * @param $attributeGroupId
     * @param $attributeCode
     * @param $sortOrder
     * @throws NoSuchEntityException
     * @throws InputException
     */
    public function assignAttributeToAttributeSet($attributeSetId, $attributeGroupId, $attributeCode, $sortOrder)
    {
        $this->attributeManagement->assign(
            Product::ENTITY,
            $attributeSetId,
            $attributeGroupId,
            $attributeCode,
            $sortOrder
        );
    }

    /**
     * Log Detailed exception
     *
     * @param $method
     * @param $storeName
     * @param $itemId
     * @param null $variantId
     * @param null $uom
     * @return void
     */
    public function logDetailedException($method, $storeName, $itemId, $variantId = null, $uom = null)
    {
        $this->logger->debug(
            sprintf(
                'Exception happened in %s for store %s, item id: %s, variant id: %s, Uom: %s',
                $method,
                $storeName,
                $itemId,
                $variantId,
                $uom
            )
        );
    }

    /**
     * Get current scope id
     *
     * @return int
     */
    public function getScopeId()
    {
        return $this->store->getWebsiteId();
    }
}
