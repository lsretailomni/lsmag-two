<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ImageSize;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Replication\Api\ReplHierarchyNodeRepositoryInterface as ReplHierarchyNodeRepository;
use \Ls\Replication\Api\ReplImageLinkRepositoryInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplHierarchyNode;
use \Ls\Replication\Model\ReplHierarchyNodeSearchResults;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyNode\CollectionFactory as ReplHierarchyNodeCollectionFactory;
use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 *
 * Create categories in magento
 */
class CategoryCreateTask
{
    /** @var CategoryFactory */
    public $categoryFactory;

    /** @var CategoryRepositoryInterface */
    public $categoryRepository;

    /** @var ReplHierarchyNodeRepository */
    public $replHierarchyNodeRepository;

    /** @var ReplImageLinkRepositoryInterface */
    public $replImageLinkRepositoryInterface;

    /** @var Logger */
    public $logger;

    /** @var CollectionFactory */
    public $collectionFactory;

    /** @var LoyaltyHelper */
    public $loyaltyHelper;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var File */
    public $file;

    /** @var LSR */
    public $lsr;

    /** @var bool */
    public $cronStatus = false;

    /** @var ProductRepositoryInterface */
    public $productRepository;

    /** @var CategoryLinkRepositoryInterface */
    public $categoryLinkRepositoryInterface;

    /** @var ReplHierarchyNodeCollectionFactory */
    public $replHierarchyNodeCollectionFactory;

    /** @var Attribute */
    public $eavAttribute;

    /** @var int */
    public $remainingRecords;

    /** @var string */
    public $hierarchyCode;

    /** @var StoreInterface $store */
    public $store;

    /** @var $langCode */
    public $langCode;

    /** @var StoreManagerInterface */
    public $storeManager;

    /**
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ReplHierarchyNodeRepository $replHierarchyNodeRepository
     * @param ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface
     * @param CollectionFactory $collectionFactory
     * @param Logger $logger
     * @param LoyaltyHelper $loyaltyHelper
     * @param File $file
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param CategoryLinkRepositoryInterface $categoryLinkRepositoryInterface
     * @param ProductRepositoryInterface $productRepository
     * @param ReplHierarchyNodeCollectionFactory $replHierarchyCollectionFactory
     * @param Attribute $eavAttribute
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepository,
        ReplHierarchyNodeRepository $replHierarchyNodeRepository,
        ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface,
        CollectionFactory $collectionFactory,
        Logger $logger,
        LoyaltyHelper $loyaltyHelper,
        File $file,
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        CategoryLinkRepositoryInterface $categoryLinkRepositoryInterface,
        ProductRepositoryInterface $productRepository,
        ReplHierarchyNodeCollectionFactory $replHierarchyCollectionFactory,
        Attribute $eavAttribute,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryFactory                    = $categoryFactory;
        $this->categoryRepository                 = $categoryRepository;
        $this->replHierarchyNodeRepository        = $replHierarchyNodeRepository;
        $this->replImageLinkRepositoryInterface   = $replImageLinkRepositoryInterface;
        $this->logger                             = $logger;
        $this->collectionFactory                  = $collectionFactory;
        $this->loyaltyHelper                      = $loyaltyHelper;
        $this->file                               = $file;
        $this->replicationHelper                  = $replicationHelper;
        $this->lsr                                = $LSR;
        $this->categoryLinkRepositoryInterface    = $categoryLinkRepositoryInterface;
        $this->productRepository                  = $productRepository;
        $this->replHierarchyNodeCollectionFactory = $replHierarchyCollectionFactory;
        $this->eavAttribute                       = $eavAttribute;
        $this->storeManager                       = $storeManager;
    }

    /**
     * Method responsible for creating categories
     *
     * @param $storeData
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
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
                $this->store    = $store;
                $this->langCode = $this->lsr->getStoreConfig(
                    LSR::SC_STORE_DATA_TRANSLATION_LANG_CODE,
                    $store->getId()
                );
                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->logger->debug('Running CategoryCreateTask for Store ' . $this->store->getName());
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_CRON_CATEGORY_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );
                    $hierarchyCode = $this->getHierarchyCode($store);
                    if (empty($hierarchyCode)) {
                        $this->logger->debug('Hierarchy Code not defined in the configuration.');
                        return;
                    }
                    $hierarchyCodeSpecificFilter = [
                        'field'          => 'HierarchyCode',
                        'value'          => $hierarchyCode,
                        'condition_type' => 'eq'
                    ];
                    $scopeIdFilter               = [
                        'field'          => 'scope_id',
                        'value'          => $this->getScopeId(),
                        'condition_type' => 'eq'
                    ];
                    $mediaAttribute              = ['image', 'small_image', 'thumbnail'];
                    $this->caterMainCategoryHierarchyNodeAddOrUpdate(
                        $hierarchyCodeSpecificFilter,
                        $mediaAttribute,
                        $scopeIdFilter
                    );
                    $this->caterSubCategoryHierarchyNodeAddOrUpdate(
                        $hierarchyCodeSpecificFilter,
                        $mediaAttribute,
                        $scopeIdFilter
                    );
                    if ($this->getRemainingRecords($store) == 0) {
                        $this->cronStatus = true;
                    }
                    $this->caterHierarchyNodeRemoval($hierarchyCode);
                    $this->updateImagesOnly();
                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_CATEGORY,
                        $store->getId(),
                        false,
                        ScopeInterface::SCOPE_STORES
                    );
                    $this->logger->debug('CategoryCreateTask Completed for Store ' . $this->store->getName());
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * Responsible for adding or updating all main categories under root category
     *
     * @param $HierarchyCodeSpecificFilter
     * @param $mediaAttribute
     * @param $scopeIdFilter
     * @return void
     */
    public function caterMainCategoryHierarchyNodeAddOrUpdate(
        $HierarchyCodeSpecificFilter,
        $mediaAttribute,
        $scopeIdFilter = false
    ) {
        $parentNodeNullFilter = ['field' => 'ParentNode', 'value' => true, 'condition_type' => 'null'];
        if ($scopeIdFilter) {
            // if the filter is set then add the scopeId into the condition as well.
            $filters = [
                $parentNodeNullFilter,
                $HierarchyCodeSpecificFilter,
                $scopeIdFilter
            ];
        } else {
            $filters = [
                $parentNodeNullFilter,
                $HierarchyCodeSpecificFilter
            ];
        }
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, -1);
        /** @var ReplHierarchyNodeSearchResults $replHierarchyNodeRepository */
        $replHierarchyNodeRepository = $this->replHierarchyNodeRepository->getList($criteria);
        /** @var ReplHierarchyNode $hierarchyNode */
        foreach ($replHierarchyNodeRepository->getItems() as $hierarchyNode) {
            try {
                if (empty($hierarchyNode->getNavId())) {
                    $hierarchyNode->setData('is_failed', 1);
                    $hierarchyNode->setData('processed_at', $this->replicationHelper->getDateTime());
                    $hierarchyNode->setData('processed', 1);
                    $hierarchyNode->setData('is_updated', 0);
                    $this->replHierarchyNodeRepository->save($hierarchyNode);
                    continue;
                }
                //** Adding Filter for Store so that we can search for Store based on Store Root Category. */
                $categoryExistData = $this->isCategoryExist($hierarchyNode->getNavId(), true);
                if (!$categoryExistData) {
                    /** @var Category $category */
                    $category = $this->categoryFactory->create();
                    $data     = [
                        'parent_id'       => $this->getRootCategoryId(),
                        'name'            => ($hierarchyNode->getDescription()) ?: $hierarchyNode->getNavId(),
                        'url_key'         => $this->oSlug($hierarchyNode->getNavId()),
                        'is_active'       => true,
                        'is_anchor'       => true,
                        'include_in_menu' => true,
                        'meta_title'      => ($hierarchyNode->getDescription()) ?: $hierarchyNode->getNavId(),
                        'nav_id'          => $hierarchyNode->getNavId(),
                        'position'        => $hierarchyNode->getChildrenOrder()
                    ];
                    $category->setData($data)->setAttributeSetId($category->getDefaultAttributeSetId());
                    if ($hierarchyNode->getImageId()) {
                        $image = $this->getImage($hierarchyNode->getImageId());
                        $category->setImage($image, $mediaAttribute, true, false);
                    }
                    // @codingStandardsIgnoreLine
                    $cat = $this->categoryRepository->save($category);
                    $cat->setStoreId($this->store->getId());
                    $cat->getResource()->saveAttribute($cat, 'nav_id');
                } else {
                    if ($hierarchyNode->getIsUpdated() == 1) {
                        if ($this->langCode == 'Default') {
                            $categoryExistData->setData(
                                'name',
                                ($hierarchyNode->getDescription()) ?: $hierarchyNode->getNavId()
                            );
                        }
                        $categoryExistData->setData('is_active', 1);
                        if ($hierarchyNode->getImageId()) {
                            $image = $this->getImage($hierarchyNode->getImageId());
                            $categoryExistData->setImage($image, $mediaAttribute, true, false);
                        }
                        $categoryExistData->setData(
                            'position',
                            $hierarchyNode->getChildrenOrder()
                        );
                        // @codingStandardsIgnoreStart
                        $this->categoryRepository->save($categoryExistData);
                    } else {
                        $categoryExistData->setStoreId($this->store->getId());
                        $categoryExistData->getResource()->saveAttribute($categoryExistData, 'nav_id');
                    }
                }
            } catch (Exception $e) {
                $this->logDetailedException(__METHOD__, $this->store->getName(), $hierarchyNode->getNavId());
                $this->logger->debug($e->getMessage());
                $hierarchyNode->setData('is_failed', 1);
            }
            // @codingStandardsIgnoreStart
            $hierarchyNode->setData('processed_at', $this->replicationHelper->getDateTime());
            $hierarchyNode->setData('processed', 1);
            $hierarchyNode->setData('is_updated', 0);
            $this->replHierarchyNodeRepository->save($hierarchyNode);
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * Responsible for creating or updating all sub categories under main categories
     *
     * @param $HierarchyCodeSpecificFilter
     * @param $mediaAttribute
     * @param bool $scopeIdFilter
     * @throws InputException
     */
    public function caterSubCategoryHierarchyNodeAddOrUpdate(
        $HierarchyCodeSpecificFilter,
        $mediaAttribute,
        $scopeIdFilter = false
    ) {
        // This is for the child/sub categories apply ParentNode Not Null Criteria
        $parentNodeNotNullFilter = ['field' => 'ParentNode', 'value' => true, 'condition_type' => 'notnull'];
        if ($scopeIdFilter) {
            // if the filter is set then add the scopeId into the condition as well.
            $filtersSub = [
                $parentNodeNotNullFilter,
                $HierarchyCodeSpecificFilter,
                $scopeIdFilter
            ];
        } else {
            $filtersSub = [
                $parentNodeNotNullFilter,
                $HierarchyCodeSpecificFilter
            ];
        }
        $criteriaSub = $this->replicationHelper->buildCriteriaForArray($filtersSub, -1);
        $criteriaSub->setSortOrders(
            [$this->replicationHelper->getSortOrderObject('Indentation')]
        );
        /** @var ReplHierarchyNodeSearchResults $replHierarchyNodeRepositorySub */
        $replHierarchyNodeRepositorySub = $this->replHierarchyNodeRepository->getList($criteriaSub);
        /** @var ReplHierarchyNode $hierarchyNodeSub */
        foreach ($replHierarchyNodeRepositorySub->getItems() as $hierarchyNodeSub) {
            try {
                if (empty($hierarchyNodeSub->getNavId())) {
                    $hierarchyNodeSub->setData('is_failed', 1);
                    $hierarchyNodeSub->setData('processed_at', $this->replicationHelper->getDateTime());
                    $hierarchyNodeSub->setData('processed', 1);
                    $hierarchyNodeSub->setData('is_updated', 0);
                    $this->replHierarchyNodeRepository->save($hierarchyNodeSub);
                    continue;
                }
                $itemCategoryId = $hierarchyNodeSub->getParentNode();
                /** @var CollectionFactory $collection */
                $collection           = $this->collectionFactory->create()
                    ->addAttributeToFilter('nav_id', $itemCategoryId)
                    ->addPathsFilter('1/' . $this->getRootCategoryId() . '/')
                    ->setPageSize(1);
                $subCategoryExistData = $this->isCategoryExist(
                    $hierarchyNodeSub->getNavId(),
                    true
                );
                if ($collection->getSize() > 0) {
                    if (!$subCategoryExistData) {
                        /** @var CategoryFactory $categorysub */
                        $categorysub = $this->categoryFactory->create();
                        $data        = [
                            // @codingStandardsIgnoreStart
                            'parent_id'       => $collection->getFirstItem()->getId(),
                            // @codingStandardsIgnoreEnd
                            'name'            => ($hierarchyNodeSub->getDescription()) ?: $hierarchyNodeSub->getNavId(),
                            'url_key'         => $this->oSlug($hierarchyNodeSub->getNavId()),
                            'is_active'       => true,
                            'is_anchor'       => true,
                            'include_in_menu' => true,
                            'meta_title'      => ($hierarchyNodeSub->getDescription()) ?: $hierarchyNodeSub->getNavId(),
                            'nav_id'          => $hierarchyNodeSub->getNavId(),
                            'position'        => $hierarchyNodeSub->getChildrenOrder()
                        ];
                        $categorysub->setData($data)->setAttributeSetId($categorysub->getDefaultAttributeSetId());
                        if ($hierarchyNodeSub->getImageId()) {
                            $imageSub = $this->getImage($hierarchyNodeSub->getImageId());
                            $categorysub->setImage($imageSub, $mediaAttribute, true, false);
                        }
                        // @codingStandardsIgnoreLine
                        $catsub = $this->categoryRepository->save($categorysub);
                        $catsub->setStoreId($this->store->getId());
                        $catsub->getResource()->saveAttribute($catsub, 'nav_id');
                    } else {
                        if ($hierarchyNodeSub->getIsUpdated() == 1) {
                            if ($this->langCode == 'Default') {
                                $subCategoryExistData->setData(
                                    'name',
                                    ($hierarchyNodeSub->getDescription()) ?: $hierarchyNodeSub->getNavId()
                                );
                            }
                            $parentCategoryExistData = $this->isCategoryExist($hierarchyNodeSub->getParentNode(), true);
                            if ($parentCategoryExistData) {
                                $newParentId = $parentCategoryExistData->getId();
                                if ($newParentId != $subCategoryExistData->getData('parent_id')) {
                                    $subCategoryExistData->move($newParentId, null);
                                }
                            } else {
                                $this->logger->debug(
                                    sprintf('Parent Category not found for Nav Id : %s', $hierarchyNodeSub->getNavId())
                                );
                            }
                            $subCategoryExistData->setData('is_active', 1);
                            if ($hierarchyNodeSub->getImageId()) {
                                $imageSub = $this->getImage($hierarchyNodeSub->getImageId());
                                $subCategoryExistData->setImage($imageSub, $mediaAttribute, true, false);
                            }
                            $subCategoryExistData->setData(
                                'position',
                                $hierarchyNodeSub->getChildrenOrder()
                            );
                            // @codingStandardsIgnoreStart                            
                            $this->categoryRepository->save($subCategoryExistData);
                        } else {
                            $subCategoryExistData->setStoreId($this->store->getId());
                            $subCategoryExistData->getResource()->saveAttribute($subCategoryExistData, 'nav_id');
                        }
                    }
                } else {
                    $hierarchyNodeSub->setData('is_failed', 1);
                }
            } catch (Exception $e) {
                $this->logDetailedException(__METHOD__, $this->store->getName(), $hierarchyNodeSub->getNavId());
                $this->logger->debug($e->getMessage());
                $hierarchyNodeSub->setData('is_failed', 1);
            }
            $hierarchyNodeSub->setData('processed_at', $this->replicationHelper->getDateTime());
            $hierarchyNodeSub->setData('processed', 1);
            $hierarchyNodeSub->setData('is_updated', 0);
            $this->replHierarchyNodeRepository->save($hierarchyNodeSub);
        }
    }

    /**
     * Responsible for disabling categories
     *
     * @param $hierarchyCode
     * @return void
     */
    public function caterHierarchyNodeRemoval($hierarchyCode)
    {
        $attribute_id = $this->eavAttribute->getIdByCode(Category::ENTITY, 'nav_id');
        $filters      = [
            ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
            ['field' => 'main_table.HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq'],
            ['field' => 'second.attribute_id', 'value' => $attribute_id, 'condition_type' => 'eq'],
            [
                'field'          => 'second.store_id',
                'value'          => !$this->storeManager->hasSingleStore() ? $this->store->getId() : 0,
                'condition_type' => 'eq'
            ]
        ];
        $criteria     = $this->replicationHelper->buildCriteriaGetDeletedOnlyWithAlias($filters, 100);
        $collection   = $this->replHierarchyNodeCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'nav_id',
            'catalog_category_entity_varchar',
            'value'
        );
        $collection->getSelect()->distinct(true);
        if ($collection->getSize() > 0) {
            /** @var ReplHierarchyNode $hierarchyNode */
            foreach ($collection as $hierarchyNode) {
                try {
                    if (!empty($hierarchyNode->getNavId())) {
                        $categoryExistData = $this->isCategoryExist($hierarchyNode->getNavId(), true);
                        if ($categoryExistData) {
                            $categoryExistData->setData('is_active', 0);
                            // @codingStandardsIgnoreLine
                            $this->categoryRepository->save($categoryExistData);
                        }
                    } else {
                        $hierarchyNode->setData('is_failed', 1);
                    }
                } catch (Exception $e) {
                    $this->logDetailedException(__METHOD__, $this->store->getName(), $hierarchyNode->getNavId());
                    $this->logger->debug($e->getMessage());
                    $hierarchyNode->setData('is_failed', 1);
                }
                $hierarchyNode->setData('processed_at', $this->replicationHelper->getDateTime());
                $hierarchyNode->setData('processed', 1);
                $hierarchyNode->setData('is_updated', 0);
                // @codingStandardsIgnoreLine
                $this->replHierarchyNodeRepository->save($hierarchyNode);
            }
        }
    }

    /**
     * Method responsible for executing cron manually from admin cron grid
     *
     * @param $storeData
     * @return int[]
     * @throws InputException|NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        $categoriesLeftToProcess = $this->getRemainingRecords($storeData);
        return [$categoriesLeftToProcess];
    }

    /**
     * Utility function for formatting
     *
     * @param $string
     * @param $parent
     * @return string
     */
    public function oSlug($string, $parent = false)
    {
        // @codingStandardsIgnoreStart
        $slug = strtolower(trim(preg_replace(
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
        if ($parent) {
            $slug = strtolower(trim(preg_replace(
                    '~[^0-9a-z]+~i',
                    '-',
                    html_entity_decode(
                        preg_replace(
                            '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i',
                            '$1',
                            htmlentities($parent, ENT_QUOTES, 'UTF-8')
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ), '-')) . '-' . $slug;

        }
        return $slug;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Check if the category already exist or not
     *
     * @param $nav_id
     * @param bool $store
     * @return bool|DataObject
     * @throws LocalizedException
     */
    public function isCategoryExist($nav_id, $store = false)
    {
        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('nav_id', $nav_id);

        if ($store) {
            $collection->addPathsFilter('1/' . $this->getRootCategoryId() . '/');
        }
        $collection->setPageSize(1);
        if ($collection->getSize()) {
            // @codingStandardsIgnoreStart
            return $collection->getFirstItem();
            // @codingStandardsIgnoreEnd
        }
        return false;
    }

    /**
     * Responsible for fetching category images
     *
     * @param $imageId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getImage($imageId = '')
    {
        $image     = '';
        $imageSize = [
            'height' => LSR::DEFAULT_IMAGE_HEIGHT,
            'width'  => LSR::DEFAULT_IMAGE_WIDTH
        ];
        /** @var ImageSize $imageSizeObject */
        $imageSizeObject = $this->loyaltyHelper->getImageSize($imageSize);
        $result          = $this->loyaltyHelper->getImageById($imageId, $imageSizeObject);
        if (!empty($result) && !empty($result['format']) && !empty($result['image'])) {
            //check if directory exists or not and if it has the proper permission or not
            $offerpath = $this->getMediaPathtoStore();
            // @codingStandardsIgnoreStart
            if (!is_dir($offerpath)) {
                $this->file->mkdir($offerpath, 0775);
            }
            $format      = strtolower($result['format']);
            $imageName   = $this->oSlug($imageId);
            $output_file = "{$imageName}.$format";
            $file        = "{$offerpath}{$output_file}";
            if (!$this->file->fileExists($file)) {
                $base64     = $result['image'];
                $image_file = fopen($file, 'wb');
                fwrite($image_file, base64_decode($base64));
                fclose($image_file);
            }
            // @codingStandardsIgnoreEnd
            $image = "{$output_file}";
        }
        return $image;
    }

    /**
     * Return the media path of the category
     *
     * @return string
     */
    public function getMediaPathtoStore()
    {
        $mediaDirectory = $this->loyaltyHelper->getMediaPathtoStore();
        return $mediaDirectory . 'catalog' . DIRECTORY_SEPARATOR . 'category' . DIRECTORY_SEPARATOR;
    }

    /**
     * Update/Add the modified/added images of the item
     *
     * @return void
     */
    public function updateImagesOnly()
    {
        $filters  = [
            ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
            ['field' => 'main_table.TableName', 'value' => 'Hierarchy Node%', 'condition_type' => 'like']
        ];
        $criteria = $this->replicationHelper->buildCriteriaGetUpdatedOnly($filters);
        $images   = $this->replImageLinkRepositoryInterface->getList($criteria)->getItems();
        if (!empty($images)) {
            foreach ($images as $image) {
                try {
                    $keyValue          = explode(',', $image->getKeyValue());
                    $navId             = $keyValue[1];
                    $categoryExistData = $this->isCategoryExist($navId, true);
                    if ($categoryExistData) {
                        $imageSub       = $this->getImage($image->getImageId());
                        $mediaAttribute = ['image', 'small_image', 'thumbnail'];
                        $categoryExistData->setImage($imageSub, $mediaAttribute, true, false);
                        $this->categoryRepository->save($categoryExistData);
                        $this->cronStatus = true;
                    }
                } catch (Exception $e) {
                    $this->logDetailedException(__METHOD__, $this->store->getName(), $navId);
                    $this->logger->debug($e->getMessage());
                    $image->setData('is_failed', 1);
                }
                $image->setData('processed_at', $this->replicationHelper->getDateTime());
                $image->setData('is_updated', 0);
                $image->setData('processed', 1);
                // @codingStandardsIgnoreLine
                $this->replImageLinkRepositoryInterface->save($image);
            }
        }
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
            $filters                = [
                ['field' => 'HierarchyCode', 'value' => $this->getHierarchyCode($storeData), 'condition_type' => 'eq'],
                ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
            ];
            $criteria               = $this->replicationHelper->buildCriteriaForArray($filters, -1);
            $this->remainingRecords = $this->replHierarchyNodeRepository->getList($criteria)->getTotalCount();
        }
        return $this->remainingRecords;
    }

    /**
     * Get configured hierarchy code
     *
     * @param $storeData
     * @return array|string
     */
    public function getHierarchyCode($storeData)
    {
        $this->hierarchyCode = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE, $storeData->getId());
        return $this->hierarchyCode;
    }

    /**
     * Get root category id
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getRootCategoryId()
    {
        return !$this->lsr->isSSM() ?
            $this->store->getRootCategoryId() :
            $this->storeManager->getDefaultStoreView()->getRootCategoryId();
    }

    /**
     * Log Detailed exception
     *
     * @param $method
     * @param $storeName
     * @param $itemId
     * @return void
     */
    public function logDetailedException($method, $storeName, $itemId)
    {
        $this->logger->debug(
            sprintf(
                'Exception happened in %s for store: %s, item id: %s',
                $method,
                $storeName,
                $itemId
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
