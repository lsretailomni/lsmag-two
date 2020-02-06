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
use Magento\Framework\Filesystem\Io\File;

/**
 * Class CategoryCreateTask
 * @package Ls\Replication\Cron
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

    /** @var Cron Checking */
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

    /**
     * CategoryCreateTask constructor.
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
        Attribute $eavAttribute
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
    }

    /**
     * execute
     */
    public function execute()
    {
        $this->logger->debug('Running CategoryCreateTask.');
        $this->replicationHelper->updateConfigValue(
            $this->replicationHelper->getDateTime(),
            LSR::SC_CRON_CATEGORY_CONFIG_PATH_LAST_EXECUTE
        );
        $hierarchyCode = $this->getHierarchyCode();
        if (empty($hierarchyCode)) {
            $this->logger->debug('Hierarchy Code not defined in the configuration.');
            return;
        }
        $hierarchyCodeSpecificFilter = [
            'field'          => 'HierarchyCode',
            'value'          => $hierarchyCode,
            'condition_type' => 'eq'
        ];
        $mediaAttribute              = ['image', 'small_image', 'thumbnail'];
        $this->caterMainCategoryHierarchyNodeAddOrUpdate($hierarchyCodeSpecificFilter, $mediaAttribute);
        $this->caterSubCategoryHierarchyNodeAddOrUpdate($hierarchyCodeSpecificFilter, $mediaAttribute);
        $remainingRecords = $this->getRemainingRecords();
        if ($this->getRemainingRecords() == 0) {
            $this->cronStatus = true;
        }
        $this->caterHierarchyNodeRemoval($hierarchyCode);
        $this->updateImagesOnly();
        $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_CATEGORY);
        $this->logger->debug('CategoryCreateTask Completed.');
    }

    /**
     * @param $HierarchyCodeSpecificFilter
     * @param $mediaAttribute
     * @return int
     */
    public function caterMainCategoryHierarchyNodeAddOrUpdate($HierarchyCodeSpecificFilter, $mediaAttribute)
    {
        $parentNodeNullFilter = ['field' => 'ParentNode', 'value' => true, 'condition_type' => 'null'];
        $filters              = [
            $parentNodeNullFilter,
            $HierarchyCodeSpecificFilter
        ];
        $criteria             = $this->replicationHelper->buildCriteriaForArray($filters, -1);
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
                $categoryExistData = $this->isCategoryExist($hierarchyNode->getNavId());
                if (!$categoryExistData) {
                    /** @var Category $category */
                    $category = $this->categoryFactory->create();
                    $data     = [
                        'parent_id'       => 2,
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
                    $this->categoryRepository->save($category);
                } else {
                    if ($hierarchyNode->getIsUpdated() == 1) {
                        $categoryExistData->setData(
                            'name',
                            ($hierarchyNode->getDescription()) ?: $hierarchyNode->getNavId()
                        );
                        if (2 != $categoryExistData->getData('parent_id')) {
                            $categoryExistData->move(2, null);
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
                    }
                }
            } catch (Exception $e) {
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
     * @param $HierarchyCodeSpecificFilter
     * @param $mediaAttribute
     * @return int
     * @throws InputException
     */
    public function caterSubCategoryHierarchyNodeAddOrUpdate($HierarchyCodeSpecificFilter, $mediaAttribute)
    {
        // This is for the child/sub categories apply ParentNode Not Null Criteria
        $parentNodeNotNullFilter = ['field' => 'ParentNode', 'value' => true, 'condition_type' => 'notnull'];
        $filtersSub              = [
            $parentNodeNotNullFilter,
            $HierarchyCodeSpecificFilter
        ];
        $criteriaSub             = $this->replicationHelper->buildCriteriaForArray($filtersSub, -1);
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
                $itemCategoryId       = $hierarchyNodeSub->getParentNode();
                $collection           = $this->collectionFactory->create()
                    ->addAttributeToFilter('nav_id', $itemCategoryId)
                    ->setPageSize(1);
                $subCategoryExistData = $this->isCategoryExist($hierarchyNodeSub->getNavId());
                if ($collection->getSize() && !$subCategoryExistData) {
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
                    $this->categoryRepository->save($categorysub);
                } else {
                    if ($hierarchyNodeSub->getIsUpdated() == 1) {
                        $subCategoryExistData->setData(
                            'name',
                            ($hierarchyNodeSub->getDescription()) ?: $hierarchyNodeSub->getNavId()
                        );
                        $parentCategoryExistData = $this->isCategoryExist($hierarchyNodeSub->getParentNode());
                        if ($parentCategoryExistData) {
                            $newParentId = $parentCategoryExistData->getId();
                            if ($newParentId != $subCategoryExistData->getData('parent_id')) {
                                $subCategoryExistData->move($newParentId, null);
                            }
                        } else {
                            $this->logger->debug('Parent Category not found for Nav Id : ' . $hierarchyNodeSub->getNavId());
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
                    }
                }
            } catch (Exception $e) {
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
     * @param $hierarchyCode
     */
    public function caterHierarchyNodeRemoval($hierarchyCode)
    {
        $attribute_id = $this->eavAttribute->getIdByCode(Category::ENTITY, 'nav_id');
        $filters      = [
            ['field' => 'main_table.HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq'],
            ['field' => 'second.attribute_id', 'value' => $attribute_id, 'condition_type' => 'eq']
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
        /** @var ReplHierarchyNode $hierarchyNode */
        foreach ($collection as $hierarchyNode) {
            try {
                if (!empty($hierarchyNode->getNavId())) {
                    $categoryExistData = $this->isCategoryExist($hierarchyNode->getNavId());
                    if ($categoryExistData) {
                        $categoryExistData->setData('is_active', 0);
                        // @codingStandardsIgnoreLine
                        $this->categoryRepository->save($categoryExistData);
                    }
                } else {
                    $hierarchyNode->setData('is_failed', 1);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $hierarchyNode->setData('is_failed', 1);
            }
            $hierarchyNode->setData('processed_at', $this->replicationHelper->getDateTime());
            $hierarchyNode->setData('IsDeleted', 0);
            $hierarchyNode->setData('processed', 1);
            $hierarchyNode->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->replHierarchyNodeRepository->save($hierarchyNode);
        }
    }

    /**
     * @return array
     */
    public function executeManually()
    {
        $this->execute();
        $categoriesLeftToProcess = $this->getRemainingRecords();
        return [$categoriesLeftToProcess];
    }

    /**
     * @param $string
     * @return string]
     */
    //TODO integrate existing slug check or check if the url already exist or not.
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
     * Check if the category already exist or not
     * @param $nav_id
     * @return bool|DataObject
     * @throws LocalizedException
     */
    public function isCategoryExist($nav_id)
    {
        $collection = $this->collectionFactory->create()
            ->addAttributeToFilter('nav_id', $nav_id)
            ->setPageSize(1);
        if ($collection->getSize()) {
            // @codingStandardsIgnoreStart
            return $collection->getFirstItem();
            // @codingStandardsIgnoreEnd
        }
        return false;
    }

    /**
     * @param string $imageId
     * @return string
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
     * @return string
     */
    public function getMediaPathtoStore()
    {
        $mediaDirectory = $this->loyaltyHelper->getMediaPathtoStore();
        return $mediaDirectory . 'catalog' . DIRECTORY_SEPARATOR . 'category' . DIRECTORY_SEPARATOR;
    }

    /**
     * Update/Add the modified/added images of the item
     */
    public function updateImagesOnly()
    {
        $filters  = [
            ['field' => 'main_table.TableName', 'value' => 'Hierarchy Node', 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaGetUpdatedOnly($filters);
        $images   = $this->replImageLinkRepositoryInterface->getList($criteria)->getItems();
        if (!empty($images)) {
            foreach ($images as $image) {
                try {
                    $keyValue          = explode(',', $image->getKeyValue());
                    $navId             = $keyValue[1];
                    $categoryExistData = $this->isCategoryExist($navId);
                    if ($categoryExistData) {
                        $imageSub       = $this->getImage($image->getImageId());
                        $mediaAttribute = ['image', 'small_image', 'thumbnail'];
                        $categoryExistData->setImage($imageSub, $mediaAttribute, true, false);
                        $this->categoryRepository->save($categoryExistData);
                        $this->cronStatus = true;
                    }
                } catch (Exception $e) {
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
     * @return int
     */
    public function getRemainingRecords()
    {
        if (!$this->remainingRecords) {
            $hierarchyCodeSpecificFilter = [
                'field'          => 'HierarchyCode',
                'value'          => $this->getHierarchyCode(),
                'condition_type' => 'eq'
            ];
            $criteria                    = $this->replicationHelper->buildCriteriaForArray([$hierarchyCodeSpecificFilter],
                -1);
            $this->remainingRecords      = $this->replHierarchyNodeRepository->getList($criteria)
                ->getTotalCount();
        }
        return $this->remainingRecords;
    }

    /**
     * @return string
     */
    public function getHierarchyCode()
    {
        if (!$this->hierarchyCode) {
            $this->hierarchyCode = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE);
        }
        return $this->hierarchyCode;
    }
}
