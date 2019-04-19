<?php

namespace Ls\Replication\Cron;

use Ls\Core\Model\LSR;
use Ls\Omni\Helper\LoyaltyHelper;
use Ls\Omni\Helper\StockHelper;
use Ls\Replication\Api\ReplAttributeValueRepositoryInterface;
use Ls\Replication\Api\ReplBarcodeRepositoryInterface as ReplBarcodeRepository;
use Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use Ls\Replication\Api\ReplHierarchyLeafRepositoryInterface as ReplHierarchyLeafRepository;
use Ls\Replication\Api\ReplImageLinkRepositoryInterface;
use Ls\Replication\Api\ReplImageRepositoryInterface as ReplImageRepository;
use Ls\Replication\Api\ReplInvStatusRepositoryInterface as ReplInvStatusRepository;
use Ls\Replication\Api\ReplItemRepositoryInterface as ReplItemRepository;
use Ls\Replication\Api\ReplItemVariantRegistrationRepositoryInterface as ReplItemVariantRegistrationRepository;
use Ls\Replication\Api\ReplPriceRepositoryInterface as ReplPriceRepository;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Replication\Model\ReplImageLink;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProTypeModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

/**
 * Class ProductCreateTask
 * @package Ls\Replication\Cron
 */
class ProductCreateTask
{
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

    /** @var Cron Checking */
    public $cronStatus = false;

    /** @var \Ls\Omni\Helper\StockHelper */
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
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface,
        LoyaltyHelper $loyaltyHelper,
        ReplicationHelper $replicationHelper,
        ReplAttributeValueRepositoryInterface $replAttributeValueRepositoryInterface,
        LoggerInterface $logger,
        LSR $LSR,
        ConfigurableProTypeModel $configurableProTypeModel,
        StockHelper $stockHelper
    ) {
        $this->factory = $factory;
        $this->item = $item;
        $this->eavConfig = $eavConfig;
        $this->configurable = $configurable;
        $this->attribute = $attribute;
        $this->productFactory = $productInterfaceFactory;
        $this->productRepository = $productRepository;
        $this->attributeMediaGalleryEntry = $attributeMediaGalleryEntry;
        $this->imageContent = $imageContent;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->itemRepository = $itemRepository;
        $this->replItemVariantRegistrationRepository = $replItemVariantRegistrationRepository;
        $this->extendedVariantValueRepository = $extendedVariantValueRepository;
        $this->imageRepository = $replImageRepository;
        $this->replHierarchyLeafRepository = $replHierarchyLeafRepository;
        $this->replBarcodeRepository = $replBarcodeRepository;
        $this->replPriceRepository = $replPriceRepository;
        $this->replInvStatusRepository = $replInvStatusRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->logger = $logger;
        $this->replImageLinkRepositoryInterface = $replImageLinkRepositoryInterface;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->replicationHelper = $replicationHelper;
        $this->replAttributeValueRepositoryInterface = $replAttributeValueRepositoryInterface;
        $this->lsr = $LSR;
        $this->configurableProTypeModel = $configurableProTypeModel;
        $this->stockHelper = $stockHelper;
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function execute()
    {
        $fullReplicationImageLinkStatus = $this->lsr->getStoreConfig(ReplEcommImageLinksTask::CONFIG_PATH_STATUS);
        $fullReplicationBarcodsStatus = $this->lsr->getStoreConfig(ReplEcommBarcodesTask::CONFIG_PATH_STATUS);
        $fullReplicationPriceStatus = $this->lsr->getStoreConfig(ReplEcommPricesTask::CONFIG_PATH_STATUS);
        $fullReplicationInvStatus = $this->lsr->getStoreConfig(ReplEcommInventoryStatusTask::CONFIG_PATH_STATUS);
        $cronCategoryCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_CATEGORY);
        $cronAttributeCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE);
        $cronAttributeVariantCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT);
        if ($cronCategoryCheck == 1 &&
            $cronAttributeCheck == 1 &&
            $cronAttributeVariantCheck == 1 &&
            $fullReplicationImageLinkStatus == 1 &&
            $fullReplicationBarcodsStatus == 1 &&
            $fullReplicationPriceStatus == 1 &&
            $fullReplicationInvStatus == 1) {
            $this->logger->debug('Running ProductCreateTask');
            $productBatchSize = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_PRODUCT_BATCHSIZE);
            /** @var \Magento\Framework\Api\SearchCriteria $criteria */
            $criteria = $this->replicationHelper->buildCriteriaForNewItems('', '', '', $productBatchSize);
            /** @var \Ls\Replication\Model\ReplItemSearchResults $items */
            $items = $this->itemRepository->getList($criteria);
            $storeId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
            /** @var \Ls\Replication\Model\ReplItem $item */
            foreach ($items->getItems() as $item) {
                try {
                    $productData = $this->productRepository->get($item->getNavId());
                    try {
                        $productData->setName($item->getDescription());
                        $productData->setMetaTitle($item->getDescription());
                        $productData->setDescription($item->getDetails());
                        $productData->setCustomAttribute("uom", $item->getBaseUnitOfMeasure());
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
                    } catch (\Exception $e) {
                        $this->logger->debug($e->getMessage());
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
                    $product = $this->productFactory->create();
                    $product->setName($item->getDescription());
                    $product->setMetaTitle($item->getDescription());
                    $product->setSku($item->getNavId());
                    $product->setUrlKey($this->oSlug($item->getDescription() . "-" . $item->getNavId()));
                    $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
                    $product->setWeight(10);
                    $product->setDescription($item->getDetails());
                    $itemPrice = $this->getItemPrice($item->getNavId());
                    if (isset($itemPrice)) {
                        $product->setPrice($itemPrice->getUnitPrice());
                    } else {
                        $product->setPrice($item->getUnitPrice());
                    }
                    $product->setAttributeSetId(4);
                    $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
                    $product->setCustomAttribute("uom", $item->getBaseUnitOfMeasure());
                    /** @var ReplBarcodeRepository $itemBarcodes */
                    $itemBarcodes = $this->_getBarcode($item->getNavId());
                    if (isset($itemBarcodes[$item->getNavId()])) {
                        $product->setCustomAttribute("barcode", $itemBarcodes[$item->getNavId()]);
                    }
                    $itemStock = $this->getInventoryStatus($item->getNavId(), $storeId);
                    $product->setStockData([
                        'use_config_manage_stock' => 1,
                        'is_in_stock' => ($itemStock > 0) ? 1 : 0,
                        'qty' => $itemStock
                    ]);
                    $productImages = $this->replicationHelper->getImageLinksByType($item->getNavId(), 'Item');
                    if ($productImages) {
                        $this->logger->debug('Found images for the item ' . $item->getNavId());
                        $product->setMediaGalleryEntries($this->getMediaGalleryEntries($productImages));
                    }
                    $this->logger->debug('trying to save product ' . $item->getNavId());
                    /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productSaved */
                    $product = $this->getProductAttributes($product, $item);
                    // @codingStandardsIgnoreStart
                    $productSaved = $this->productRepository->save($product);
                    $variants = $this->getProductVarients($item->getNavId());
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
                $this->cronStatus = true;
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
                $this->updatePriceOnly();
                $this->updateInventoryOnly();
            }
            $this->logger->debug('End ProductCreateTask');
        } else {
            $this->logger->debug("Product Replication cron fails because custom category, 
            custom attribute or full image replication cron not executed successfully.");
        }
        $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_PRODUCT);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function executeManually()
    {
        $this->execute();
        $criteria = $this->replicationHelper->buildCriteriaForNewItems('', '', '', -1);
        $items = $this->itemRepository->getList($criteria);
        $itemsLeftToProcess = count($items->getItems());
        return [$itemsLeftToProcess];
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param \Ls\Replication\Model\ReplItem $item
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getProductAttributes(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        \Ls\Replication\Model\ReplItem $item
    ) {
        $criteria = $this->replicationHelper->buildCriteriaForProductAttributes($item->getNavId(), 100);
        /** @var \Ls\Replication\Model\ReplAttributeValueSearchResults $items */
        $items = $this->replAttributeValueRepositoryInterface->getList($criteria);
        /** @var \Ls\Replication\Model\ReplAttributeValue $item */
        foreach ($items->getItems() as $item) {
            $formattedCode = $this->replicationHelper->formatAttributeCode($item->getCode());
            $attribute = $this->eavConfig->getAttribute('catalog_product', $formattedCode);
            if ($attribute->getFrontendInput() == 'select') {
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
     * @throws \Exception
     */
    private function getMediaGalleryEntries($productImages)
    {
        $galleryArray = [];
        $i = 0;
        /** @var \Ls\Replication\Model\ReplImageLink $image */
        foreach ($productImages as $image) {
            $imageSize = [
                'height' => $this->lsr::DEFAULT_ITEM_IMAGE_HEIGHT,
                'width' => $this->lsr::DEFAULT_ITEM_IMAGE_WIDTH
            ];
            /** @var \Ls\Omni\Client\Ecommerce\Entity\ImageSize $imageSizeObject */
            $imageSizeObject = $this->loyaltyHelper->getImageSize($imageSize);
            $result = $this->loyaltyHelper->getImageById($image->getImageId(), $imageSizeObject);
            if ($result) {
                $i++;
                /** @var \Magento\Framework\Api\ImageContent $imageContent */
                $imageContent = $this->imageContent->create()
                    ->setBase64EncodedData($result->getImage())
                    ->setName($image->getImageId() . ".jpg")
                    ->setType($this->getMimeType($result->getImage()));
                $this->attributeMediaGalleryEntry->setMediaType("image")
                    ->setLabel("Product Image")
                    ->setPosition($i)
                    ->setDisabled(false)
                    ->setTypes(
                        [
                            "image",
                            "small_image",
                            "thumbnail"
                        ]
                    )->setContent($imageContent);
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
     * @throws \Magento\Framework\Exception\LocalizedException
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
        $hierarchyCode = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE);
        if (empty($hierarchyCode)) {
            $this->logger->debug("Hierarchy Code not defined in the configuration.");
            return;
        }
        $filters = [
            ['field' => 'NodeId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, 100);
        /** @var \Ls\Replication\Model\ReplHierarchyLeafSearchResults $replHierarchyLeafRepository */
        $replHierarchyLeafRepository = $this->replHierarchyLeafRepository->getList($criteria);
        foreach ($replHierarchyLeafRepository->getItems() as $hierarchyLeaf) {
            try {
                $categoryArray = $this->findCategoryIdFromFactory($hierarchyLeaf->getNodeId());
                if (!empty($categoryArray)) {
                    // @codingStandardsIgnoreStart
                    $this->categoryLinkManagement->assignProductToCategories($hierarchyLeaf->getNavId(),
                        $categoryArray);
                    $hierarchyLeaf->setData('processed', '1');
                    $hierarchyLeaf->setData('is_updated', '0');
                    $this->replHierarchyLeafRepository->save($hierarchyLeaf);
                    // @codingStandardsIgnoreEnd
                }
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
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
     * Return all Variants
     * @param type $itemid
     * @return type
     */
    private function getProductVarients($itemid)
    {
        $this->searchCriteriaBuilder->addFilter('ItemId', $itemid);
        $this->searchCriteriaBuilder->addFilter('IsDeleted', '0');
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $variants = $this->replItemVariantRegistrationRepository->getList($searchCriteria)->getItems();
        return $variants;
    }

    /**
     * Return all updated variants only
     * @param type $filters
     * @return type
     */
    private function getUpdatedProductVariants($filters)
    {
        /** @var \Magento\Framework\Api\SearchCriteria $criteria */
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters);
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
        /** @var \Magento\Framework\Api\SearchCriteria $criteria */
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly($filters);
        $items = $this->itemRepository->getList($criteria);
        return $items;
    }

    /**
     * Return all updated variants only
     * @param type $filters
     * @return type
     */
    private function getDeletedVariantsOnly($filters)
    {
        /** @var \Magento\Framework\Api\SearchCriteria $criteria */
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly($filters);
        $variants = $this->replItemVariantRegistrationRepository->getList($criteria)->getItems();
        return $variants;
    }

    /**
     * @param $code
     * @param $value
     * @return null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getOptionIDByCode($code, $value)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $code);
        $optionID = $attribute->getSource()->getOptionId($value);
        return $optionID;
    }

    /**
     * @param $itemId
     * @return array
     */
    public function _getAttributesCodes($itemId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('ItemId', $itemId)->create();
        $attributeCodes = $this->extendedVariantValueRepository->getList($searchCriteria)->getItems();
        /** @var \Ls\Replication\Model\ReplExtendedVariantValue $valueCode */
        $finalCodes = [];
        foreach ($attributeCodes as $valueCode) {
            $formattedCode = $this->replicationHelper->formatAttributeCode($valueCode->getCode());
            $finalCodes[$valueCode->getDimensions()] = $formattedCode;
            $valueCode->setData('processed', '1');
            // @codingStandardsIgnoreStart
            $this->extendedVariantValueRepository->save($valueCode);
            // @codingStandardsIgnoreEnd
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
        $allBarCodes = [];
        /** @var ReplBarcodeRepository $itemBarcodes */
        $itemBarcodes = $this->replBarcodeRepository->getList($searchCriteria)->getItems();
        foreach ($itemBarcodes as $itemBarcode) {
            $sku = $itemBarcode->getItemId() .
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
        $items = [];
        /** @var ReplItemRepository $items */
        $items = $this->itemRepository->getList($searchCriteria)->getItems();
        foreach ($items as $item) {
            return $item;
        }
    }

    /**
     * Item Price
     * @param $itemId
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
        $items = [];
        $searchCriteria = $this->replicationHelper->buildCriteriaForArray($filters, 1);
        /** @var ReplPriceRepository $items */
        $items = $this->replPriceRepository->getList($searchCriteria)->getItems();
        foreach ($items as $item) {
            return $item;
        }
    }

    /**
     * Update/Add the modified/added variants of the item
     */
    public function updateVariantsOnly()
    {
        $filters = [
            ['field' => 'ItemId', 'value' => true, 'condition_type' => 'notnull']
        ];
        $variants = $this->getUpdatedProductVariants($filters);
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
            } catch (\Exception $e) {
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
        $items = $this->getDeletedItemsOnly($filters);

        if (!empty($items->getItems())) {
            try {
                foreach ($items->getItems() as $value) {
                    $sku = $value->getNavId();
                    $productData = $this->productRepository->get($sku);
                    $productData->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
                    // @codingStandardsIgnoreStart
                    $this->productRepository->save($productData);
                    $value->setData('is_updated', '0');
                    $value->setData('processed', '1');
                    $value->setData('IsDeleted', '0');
                    $this->itemRepository->save($value);
                    // @codingStandardsIgnoreEnd
                }
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Cater SimpleProducts Removal
     */
    public function caterVariantsRemoval()
    {
        $filters = [
            ['field' => 'ItemId', 'value' => true, 'condition_type' => 'notnull']
        ];
        $variants = $this->getDeletedVariantsOnly($filters);

        if (count($variants) > 0) {
            try {
                /** @var \Ls\Replication\Model\ReplItemVariantRegistration $value */
                foreach ($variants as $value) {
                    $d1 = (($value->getVariantDimension1()) ? $value->getVariantDimension1() : '');
                    $d2 = (($value->getVariantDimension2()) ? $value->getVariantDimension2() : '');
                    $d3 = (($value->getVariantDimension3()) ? $value->getVariantDimension3() : '');
                    $d4 = (($value->getVariantDimension4()) ? $value->getVariantDimension4() : '');
                    $d5 = (($value->getVariantDimension5()) ? $value->getVariantDimension5() : '');
                    $d6 = (($value->getVariantDimension6()) ? $value->getVariantDimension6() : '');
                    $itemId = $value->getItemId();
                    $productData = $this->productRepository->get($itemId);
                    $attributeCodes = $this->_getAttributesCodes($productData->getSku());
                    $configurableAttributes = [];
                    foreach ($attributeCodes as $keyCode => $valueCode) {
                        if (isset($keyCode) && $keyCode != '') {
                            $code = $valueCode;
                            $codeValue = ${'d' . $keyCode};
                            $configurableAttributes[] = ["code" => $code, 'value' => $codeValue];
                        }
                    }
                    $associatedSimpleProduct = $this->getConfAssoProductId($productData, $configurableAttributes);
                    if ($associatedSimpleProduct != null) {
                        $associatedSimpleProduct->setStatus(
                            \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED
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
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * @param $product
     * @param $nameValueList
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getConfAssoProductId($product, $nameValueList)
    {
        //get configurable products attributes array with all values
        // with lable (supper attribute which use for configuration)
        $assPro = null;
        $optionsData = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        $superAttrList = [];
        $superAttrOptions = [];
        $attributeValues = [];

        // prepare array with attribute values
        foreach ($optionsData as $option) {
            $superAttrList[] = [
                'name' => $option['frontend_label'],
                'code' => $option['attribute_code'],
                'id' => $option['attribute_id']
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
            // it return complete product accoring to attribute values which you pass
        }
        return $assPro;
    }

    /**
     * Update/Add the modified/added images of the item
     */
    public function updateAndAddNewImageOnly()
    {
        //
        $filters = [
            ['field' => 'TableName', 'value' => 'Item%', 'condition_type' => 'like'],
            ['field' => 'TableName', 'value' => 'Item Category', 'condition_type' => 'neq']

        ];
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, 2000);

        /** @var \Ls\Replication\Model\ReplImageLinkSearchResults $images */
        $images = $this->replImageLinkRepositoryInterface->getList($criteria);

        $processedItems = [];

        if ($images->getTotalCount() > 0) {
            /** @var \Ls\Replication\Model\ReplImage $image */
            foreach ($images->getItems() as $image) {
                if (in_array($image->getKeyValue(), $processedItems)) {
                    continue;
                }
                try {
                    if ($image->getTableName() == "Item" || $image->getTableName() == "Item Variant") {
                        /** @var ReplImageLink $image */
                        $additionalFilters = [];
                        if ($image->getTableName() == "Item") {
                            $additionalFilters = ['field' => 'TableName', 'value' => 'Item', 'condition_type' => 'eq'];
                        } elseif ($image->getTableName() == "Item Variant") {
                            $additionalFilters =
                                ['field' => 'TableName', 'value' => 'Item Variant', 'condition_type' => 'eq'];
                        }
                        $filters = [
                            $additionalFilters,
                            ['field' => 'KeyValue', 'value' => $image->getKeyValue(), 'condition_type' => 'eq'],
                            ['field' => 'isDeleted', 'value' => 0, 'condition_type' => 'eq']
                        ];
                        $criteria = $this->replicationHelper->buildExitCriteriaForArray($filters, 100);
                        $allImages = $this->replImageLinkRepositoryInterface->getList($criteria)->getItems();
                        $item = $image->getKeyValue();
                        $item = str_replace(',', '-', $item);
                        $image->setData('is_updated', '0');
                        $image->setData('processed', '1');
                        // @codingStandardsIgnoreStart
                        $this->replImageLinkRepositoryInterface->save($image);
                        /* @var ProductRepositoryInterface $productData */
                        $productData = $this->productRepository->get($item);
                        $galleryImage = $allImages;
                        $productData->setMediaGalleryEntries($this->getMediaGalleryEntries($galleryImage));
                        $this->productRepository->save($productData);
                        // @codingStandardsIgnoreEnd
                        // Adding items into an array whose images are processed.
                        $processedItems[] = $image->getKeyValue();
                    }
                } catch (\Exception $e) {
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
            /** @var \Ls\Replication\Model\ReplBarcodeSearchResults $replBarcodes */
            $replBarcodes = $this->replBarcodeRepository->getList($criteria);
            if ($replBarcodes->getTotalCount() > 0) {
                /** @var \Ls\Replication\Model\ReplBarcode $replBarcode */
                foreach ($replBarcodes->getItems() as $replBarcode) {
                    try {
                        if (!$replBarcode->getVariantId()) {
                            $sku = $replBarcode->getItemId();
                        } else {
                            $sku = $replBarcode->getItemId() . '-' . $replBarcode->getVariantId();
                        }
                        $productData = $this->productRepository->get($sku);
                        if (isset($productData)) {
                            $productData->setCustomAttribute("barcode", $replBarcode->getNavId());
                            // @codingStandardsIgnoreStart
                            $this->productRepository->save($productData);
                            $replBarcode->setData('is_updated', '0');
                            $replBarcode->setData('processed', '1');
                            $this->replBarcodeRepository->save($replBarcode);
                            // @codingStandardsIgnoreEnd
                        }
                    } catch (\Exception $e) {
                        $this->logger->debug($e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Update the modified price of the items & item variants
     */
    public function updatePriceOnly()
    {
        $filters = [];
        $criteria = $this->replicationHelper->buildCriteriaGetUpdatedOnly($filters);
        /** @var \Ls\Replication\Model\ReplPriceSearchResults $replPrices */
        $replPrices = $this->replPriceRepository->getList($criteria);
        if ($replPrices->getTotalCount() > 0) {
            /** @var \Ls\Replication\Model\ReplPrice $replPrice */
            foreach ($replPrices->getItems() as $replPrice) {
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
                        $this->productRepository->save($productData);
                        $replPrice->setData('is_updated', '0');
                        $replPrice->setData('processed', '1');
                        $this->replPriceRepository->save($replPrice);
                        // @codingStandardsIgnoreEnd
                    }
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
        }
    }

    /**
     * Update the inventory of the items & item variants
     */
    public function updateInventoryOnly()
    {
        $filters = [];
        $criteria = $this->replicationHelper->buildCriteriaGetUpdatedOnly($filters);
        /** @var \Ls\Replication\Model\ReplInvStatusSearchResults $replInvStatusArray */
        $replInvStatusArray = $this->replInvStatusRepository->getList($criteria);
        if ($replInvStatusArray->getTotalCount() > 0) {
            /** @var \Ls\Replication\Model\ReplInvStatus $replInvStatus */
            foreach ($replInvStatusArray->getItems() as $replInvStatus) {
                try {
                    if (!$replInvStatus->getVariantId()) {
                        $sku = $replInvStatus->getItemId();
                    } else {
                        $sku = $replInvStatus->getItemId() . '-' . $replInvStatus->getVariantId();
                    }
                    $productData = $this->productRepository->get($sku);
                    if (isset($productData)) {
                        $productData->setStockData([
                            'is_in_stock' => ($replInvStatus->getQuantity() > 0) ? 1 : 0,
                            'qty' => $replInvStatus->getQuantity()
                        ]);
                        // @codingStandardsIgnoreStart
                        $this->productRepository->save($productData);
                        $replInvStatus->setData('is_updated', '0');
                        $replInvStatus->setData('processed', '1');
                        $this->replInvStatusRepository->save($replInvStatus);
                        // @codingStandardsIgnoreEnd
                    }
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
        }
    }

    /** For product variants, get image from item_image_link with type item variant
     * @param $configProduct
     * @param $item
     * @param $itemBarcodes
     * @param $variants
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function createConfigurableProducts($configProduct, $item, $itemBarcodes, $variants)
    {
        // get those attribute codes which are assigned to product.
        $attributesCode = $this->_getAttributesCodes($item->getNavId());
        $this->logger->debug('Attribute code array');
        $attributesIds = [];
        $associatedProductIds = [];
        $configurableProductsData = [];
        foreach ($attributesCode as $value) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute\Interceptor $attribute */
            $attribute = $this->eavConfig->getAttribute('catalog_product', $value);
            $attributeOptions[$attribute->getId()] = $attribute->getSource()->getAllOptions();
            $attributesIds[] = $attribute->getId();
        }

        $storeId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
        /** @var \Ls\Replication\Model\ReplItemVariantRegistration $value */
        foreach ($variants as $value) {
            $sku = $value->getItemId() . '-' . $value->getVariantId();
            try {
                $productData = $this->productRepository->get($sku);
                try {
                    $productData->setName($value->getDescription());
                    $productData->setMetaTitle($value->getDescription());
                    $productData->setDescription($value->getDetails());
                    $productData->setCustomAttribute("uom", $value->getBaseUnitOfMeasure());
                    $itemPrice = $this->getItemPrice($value->getItemId(), $value->getVariantId());
                    if (isset($itemPrice)) {
                        $productData->setPrice($itemPrice->getUnitPrice());
                    }
                    $productImages = $this->replicationHelper->getImageLinksByType(
                        $value->getItemId() . ',' . $value->getVariantId(),
                        'Item Variant'
                    );
                    if ($productImages) {
                        $productData->setMediaGalleryEntries($this->getMediaGalleryEntries($productImages));
                    }
                    $productData->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                    // @codingStandardsIgnoreStart
                    $productData->save();
                    $value->setData('processed', '1');
                    $value->setData('is_updated', '0');
                    $this->replItemVariantRegistrationRepository->save($value);
                    // @codingStandardsIgnoreEnd
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // check which attributes are available to be set as variant option.
                $d1 = (($value->getVariantDimension1()) ? $value->getVariantDimension1() : '');
                $d2 = (($value->getVariantDimension2()) ? $value->getVariantDimension2() : '');
                $d3 = (($value->getVariantDimension3()) ? $value->getVariantDimension3() : '');
                $d4 = (($value->getVariantDimension4()) ? $value->getVariantDimension4() : '');
                $d5 = (($value->getVariantDimension5()) ? $value->getVariantDimension5() : '');
                $d6 = (($value->getVariantDimension6()) ? $value->getVariantDimension6() : '');

                /** @var \Magento\Catalog\Api\Data\ProductInterface $productV */
                $productV = $this->productFactory->create();
                $dMerged = (($d1) ? '-' . $d1 : '') . (($d2) ? '-' . $d2 : '') . (($d3) ? '-' . $d3 : '');
                $productV->setName($item->getDescription() . $dMerged);
                $productV->setSku($sku);
                $itemPrice = $this->getItemPrice($value->getItemId(), $value->getVariantId());
                if (isset($itemPrice)) {
                    $productV->setPrice($itemPrice->getUnitPrice());
                } else {
                    $productV->setPrice($item->getUnitPrice());
                }
                $productV->setAttributeSetId(4);
                $productV->setWebsiteIds([1]);
                $productV->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
                $productV->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
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
                    $this->logger->debug('Found images for the item ' . $item->getNavId());
                    $productV->setMediaGalleryEntries($this->getMediaGalleryEntries($productImages));
                }

                $productV->setCustomAttribute("uom", $item->getBaseUnitOfMeasure());
                if (isset($itemBarcodes[$sku])) {
                    $productV->setCustomAttribute("barcode", $itemBarcodes[$sku]);
                }
                $itemStock = $this->getInventoryStatus($value->getItemId(), $storeId, $value->getVariantId());
                $productV->setStockData([
                    'use_config_manage_stock' => 1,
                    'is_in_stock' => ($itemStock > 0) ? 1 : 0,
                    'is_qty_decimal' => 0,
                    'qty' => $itemStock
                ]);
                /** @var \Magento\Catalog\Api\Data\ProductInterface $productSaved */
                // @codingStandardsIgnoreStart
                $productSaved = $this->productRepository->save($productV);
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
                'product_id' => $productId,
                'position' => $attributeKey
            ];
            try {
                // @codingStandardsIgnoreStart
                $this->attribute->setData($data)->save();
                // @codingStandardsIgnoreEnd
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $configProduct->setTypeId("configurable"); // Setting Product Type As Configurable
        $configProduct->setAffectConfigurableProductAttributes(4);
        $this->configurable->setUsedProductAttributeIds($attributesIds, $configProduct);
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
            $searchCriteria = $this->replicationHelper->buildCriteriaForArray($filters, 1);
            $inventoryStatus = [];
            /** @var ReplInvStatusRepository $inventoryStatus */
            $inventoryStatus = $this->replInvStatusRepository->getList($searchCriteria)->getItems();
            $inventoryStatus = reset($inventoryStatus);
            /** @var \Ls\Replication\Model\ReplInvStatus $invStatus */
            $qty = $inventoryStatus->getQuantity();
            $inventoryStatus->setData('is_updated', '0');
            $inventoryStatus->setData('processed', '1');
            $this->replInvStatusRepository->save($inventoryStatus);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        return $qty;
    }
}
