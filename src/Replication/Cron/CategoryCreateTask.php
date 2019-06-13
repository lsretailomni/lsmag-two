<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Replication\Api\ReplHierarchyLeafRepositoryInterface as ReplHierarchyLeafRepository;
use \Ls\Replication\Api\ReplHierarchyNodeRepositoryInterface as ReplHierarchyNodeRepository;
use \Ls\Replication\Api\ReplImageLinkRepositoryInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyLeaf\CollectionFactory as ReplHierarchyLeafCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyNode\CollectionFactory as ReplHierarchyNodeCollectionFactory;
use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Filesystem\Io\File;
use Psr\Log\LoggerInterface;

/**
 * Class CategoryCreateTask
 * @package Ls\Replication\Cron
 */
class CategoryCreateTask
{

    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_category';

    /** @var CategoryFactory */
    public $categoryFactory;

    /** @var CategoryRepositoryInterface */
    public $categoryRepository;

    /** @var ReplHierarchyNodeRepository */
    public $replHierarchyNodeRepository;

    /** @var ReplHierarchyLeafRepository */
    public $replHierarchyLeafRepository;

    /** @var ReplImageLinkRepositoryInterface */
    public $replImageLinkRepositoryInterface;

    /** @var LoggerInterface */
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

    /**
     * @var ReplHierarchyLeafCollectionFactory
     */
    public $replHierarchyLeafCollectionFactory;

    /**
     * @var ReplHierarchyNodeCollectionFactory
     */
    public $replHierarchyNodeCollectionFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    public $eavAttribute;

    /**
     * CategoryCreateTask constructor.
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ReplHierarchyNodeRepository $replHierarchyNodeRepository
     * @param ReplHierarchyLeafRepository $replHierarchyLeafRepository
     * @param ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param LoyaltyHelper $loyaltyHelper
     * @param File $file
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param CategoryLinkRepositoryInterface $categoryLinkRepositoryInterface
     * @param ProductRepositoryInterface $productRepository
     * @param ReplHierarchyLeafCollectionFactory $replHierarchyLeafCollectionFactory
     * @param ReplHierarchyNodeCollectionFactory $replHierarchyCollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepository,
        ReplHierarchyNodeRepository $replHierarchyNodeRepository,
        ReplHierarchyLeafRepository $replHierarchyLeafRepository,
        ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        LoyaltyHelper $loyaltyHelper,
        File $file,
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        CategoryLinkRepositoryInterface $categoryLinkRepositoryInterface,
        ProductRepositoryInterface $productRepository,
        ReplHierarchyLeafCollectionFactory $replHierarchyLeafCollectionFactory,
        ReplHierarchyNodeCollectionFactory $replHierarchyCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->replHierarchyNodeRepository = $replHierarchyNodeRepository;
        $this->replHierarchyLeafRepository = $replHierarchyLeafRepository;
        $this->replImageLinkRepositoryInterface = $replImageLinkRepositoryInterface;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->file = $file;
        $this->replicationHelper = $replicationHelper;
        $this->lsr = $LSR;
        $this->categoryLinkRepositoryInterface = $categoryLinkRepositoryInterface;
        $this->productRepository = $productRepository;
        $this->replHierarchyLeafCollectionFactory = $replHierarchyLeafCollectionFactory;
        $this->replHierarchyNodeCollectionFactory = $replHierarchyCollectionFactory;
        $this->eavAttribute = $eavAttribute;
    }

    /**
     * execute
     */
    public function execute()
    {
        $this->replicationHelper->updateConfigValue(date('d M,Y h:i:s A'), self::CONFIG_PATH_LAST_EXECUTE);
        $this->logger->debug("Running CategoryCreateTask");
        // for defning category images to the product group
        $hierarchyCode = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE);
        if (empty($hierarchyCode)) {
            $this->logger->debug("Hierarchy Code not defined in the configuration.");
            return;
        }
        $hierarchyCodeSpecificFilter = [
            'field' => 'HierarchyCode',
            'value' => $hierarchyCode,
            'condition_type' => 'eq'
        ];
        $mediaAttribute = ['image', 'small_image', 'thumbnail'];
        $mainCategoryHierarchyNodeAddOrUpdateCounter = $this->caterMainCategoryHierarchyNodeAddOrUpdate(
            $hierarchyCodeSpecificFilter,
            $mediaAttribute
        );
        $subCategoryHierarchyNodeAddOrUpdateCounter = $this->caterSubCategoryHierarchyNodeAddOrUpdate(
            $hierarchyCodeSpecificFilter,
            $mediaAttribute
        );
        $hierarchyNodeDeletedCounter = $this->caterHierarchyNodeRemoval($hierarchyCode);
        $hierarchyLeafDeletedCounter = $this->caterHierarchyLeafRemoval($hierarchyCode);
        if ($mainCategoryHierarchyNodeAddOrUpdateCounter == 0 &&
            $subCategoryHierarchyNodeAddOrUpdateCounter == 0 &&
            $hierarchyNodeDeletedCounter == 0 &&
            $hierarchyLeafDeletedCounter == 0) {
            $this->cronStatus = true;
        }
        //Update the Modified Images
        $this->updateImagesOnly();
        $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_CATEGORY);
        $this->logger->debug("CategoryCreateTask Completed");
    }

    /**
     * @param $HierarchyCodeSpecificFilter
     * @param $mediaAttribute
     * @return int
     */
    public function caterMainCategoryHierarchyNodeAddOrUpdate($HierarchyCodeSpecificFilter, $mediaAttribute)
    {
        $parentNodeNullFilter = ['field' => 'ParentNode', 'value' => true, 'condition_type' => 'null'];
        $filters = [
            $parentNodeNullFilter,
            $HierarchyCodeSpecificFilter
        ];
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, 100);
        /** @var \Ls\Replication\Model\ReplHierarchyNodeSearchResults $replHierarchyNodeRepository */
        $replHierarchyNodeRepository = $this->replHierarchyNodeRepository->getList($criteria);
        /** @var \Ls\Replication\Model\ReplHierarchyNode $hierarchyNode */
        foreach ($replHierarchyNodeRepository->getItems() as $hierarchyNode) {
            try {
                if (empty($hierarchyNode->getNavId())) {
                    continue;
                }
                $categoryExistData = $this->isCategoryExist($hierarchyNode->getNavId());
                if (!$categoryExistData) {
                    /** @var \Magento\Catalog\Model\Category $category */
                    $category = $this->categoryFactory->create();
                    $data = [
                        'parent_id' => 2,
                        'name' => ($hierarchyNode->getDescription()) ?
                            $hierarchyNode->getDescription() : $hierarchyNode->getNavId(),
                        'url_key' => $this->oSlug($hierarchyNode->getNavId()),
                        'is_active' => true,
                        'is_anchor' => false,
                        'include_in_menu' => true,
                        'meta_title' => ($hierarchyNode->getDescription()) ?
                            $hierarchyNode->getDescription() : $hierarchyNode->getNavId(),
                        'nav_id' => $hierarchyNode->getNavId()
                    ];
                    $category->setData($data)->setAttributeSetId($category->getDefaultAttributeSetId());
                    if ($hierarchyNode->getImageId()) {
                        $image = $this->getImage($hierarchyNode->getImageId());
                        $category->setImage($image, $mediaAttribute, true, false);
                    }
                    // @codingStandardsIgnoreStart
                    $this->categoryRepository->save($category);
                    $hierarchyNode->setData('processed', '1');
                    $this->replHierarchyNodeRepository->save($hierarchyNode);
                    // @codingStandardsIgnoreEnd
                } else {
                    if ($hierarchyNode->getIsUpdated() == 1) {
                        $categoryExistData->setData(
                            'name',
                            ($hierarchyNode->getDescription()) ?
                                $hierarchyNode->getDescription() : $hierarchyNode->getNavId()
                        );
                        $categoryExistData->setData('is_active', 1);
                        if ($hierarchyNode->getImageId()) {
                            $image = $this->getImage($hierarchyNode->getImageId());
                            $categoryExistData->setImage($image, $mediaAttribute, true, false);
                        }
                        // @codingStandardsIgnoreStart
                        $this->categoryRepository->save($categoryExistData);
                        $hierarchyNode->setData('is_updated', '0');
                        $this->replHierarchyNodeRepository->save($hierarchyNode);
                        // @codingStandardsIgnoreEnd
                    }
                }
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        return count($replHierarchyNodeRepository->getItems());
    }

    /**
     * @param $HierarchyCodeSpecificFilter
     * @param $mediaAttribute
     * @return int
     */
    public function caterSubCategoryHierarchyNodeAddOrUpdate($HierarchyCodeSpecificFilter, $mediaAttribute)
    {
        // This is for the child/sub categories apply ParentNode Not Null Criteria
        $parentNodeNotNullFilter = ['field' => 'ParentNode', 'value' => true, 'condition_type' => 'notnull'];
        $filtersSub = [
            $parentNodeNotNullFilter,
            $HierarchyCodeSpecificFilter
        ];
        $criteriaSub = $this->replicationHelper->buildCriteriaForArray($filtersSub, 100);
        /** @var \Ls\Replication\Model\ReplHierarchyNodeSearchResults $replHierarchyNodeRepositorySub */
        $replHierarchyNodeRepositorySub = $this->replHierarchyNodeRepository->getList($criteriaSub);
        /** @var \Ls\Replication\Model\ReplHierarchyNode $hierarchyNodeSub */
        foreach ($replHierarchyNodeRepositorySub->getItems() as $hierarchyNodeSub) {
            try {
                $itemCategoryId = $hierarchyNodeSub->getParentNode();
                $collection = $this->collectionFactory->create()
                    ->addAttributeToFilter('nav_id', $itemCategoryId)
                    ->setPageSize(1);
                $subCategoryExistData = $this->isCategoryExist($hierarchyNodeSub->getNavId());
                if ($collection->getSize() && !$subCategoryExistData) {
                    /** @var \Magento\Catalog\Model\CategoryFactory $categorysub */
                    $categorysub = $this->categoryFactory->create();
                    $data = [
                        // @codingStandardsIgnoreStart
                        'parent_id' => $collection->getFirstItem()->getId(),
                        // @codingStandardsIgnoreEnd
                        'name' => ($hierarchyNodeSub->getDescription()) ?
                            $hierarchyNodeSub->getDescription() : $hierarchyNodeSub->getNavId(),
                        'url_key' => $this->oSlug($hierarchyNodeSub->getNavId()),
                        'is_active' => true,
                        'is_anchor' => true,
                        'include_in_menu' => true,
                        'meta_title' => ($hierarchyNodeSub->getDescription()) ?
                            $hierarchyNodeSub->getDescription() : $hierarchyNodeSub->getNavId(),
                        'nav_id' => $hierarchyNodeSub->getNavId()
                    ];
                    $categorysub->setData($data)->setAttributeSetId($categorysub->getDefaultAttributeSetId());
                    if ($hierarchyNodeSub->getImageId()) {
                        $imageSub = $this->getImage($hierarchyNodeSub->getImageId());
                        $categorysub->setImage($imageSub, $mediaAttribute, true, false);
                    }
                    // @codingStandardsIgnoreStart
                    $this->categoryRepository->save($categorysub);
                    $hierarchyNodeSub->setData('processed', '1');
                    $this->replHierarchyNodeRepository->save($hierarchyNodeSub);
                    // @codingStandardsIgnoreEnd
                } else {
                    if ($hierarchyNodeSub->getIsUpdated() == 1) {
                        $subCategoryExistData->setData(
                            'name',
                            ($hierarchyNodeSub->getDescription()) ?
                                $hierarchyNodeSub->getDescription() : $hierarchyNodeSub->getNavId()
                        );
                        $subCategoryExistData->setData('is_active', 1);
                        if ($hierarchyNodeSub->getImageId()) {
                            $imageSub = $this->getImage($hierarchyNodeSub->getImageId());
                            $subCategoryExistData->setImage($imageSub, $mediaAttribute, true, false);
                        }
                        // @codingStandardsIgnoreStart
                        $this->categoryRepository->save($subCategoryExistData);
                        $hierarchyNodeSub->setData('is_updated', '0');
                        $this->replHierarchyNodeRepository->save($hierarchyNodeSub);
                        // @codingStandardsIgnoreEnd
                    }
                }
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        return count($replHierarchyNodeRepositorySub->getItems());
    }

    /**
     * @param $hierarchyCodeSpecificFilter
     * @return int
     */
    public function caterHierarchyNodeRemoval($hierarchyCode)
    {
        $attribute_id = $this->eavAttribute->getIdByCode(\Magento\Catalog\Model\Category::ENTITY, 'nav_id');
        $filters =  [
            ['field' => 'main_table.HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq'],
            ['field' => 'second.attribute_id', 'value' => $attribute_id, 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnlyWithAlias($filters, 100);
        $collection = $this->replHierarchyNodeCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'nav_id',
            'catalog_category_entity_varchar',
            'value'
        );
        /** @var \Ls\Replication\Model\ReplHierarchyNode $hierarchyNode */
        foreach ($collection as $hierarchyNode) {
            try {
                if (!empty($hierarchyNode->getNavId())) {
                    $categoryExistData = $this->isCategoryExist($hierarchyNode->getNavId());
                    if ($categoryExistData) {
                        $categoryExistData->setData('is_active', 0);
                        // @codingStandardsIgnoreStart
                        $this->categoryRepository->save($categoryExistData);
                        // @codingStandardsIgnoreEnd
                        $hierarchyNode->setData('is_processed', '1');
                        $hierarchyNode->setData('IsDeleted', '0');
                        $hierarchyNode->setData('is_updated', '0');
                        // @codingStandardsIgnoreStart
                        $this->replHierarchyNodeRepository->save($hierarchyNode);
                        // @codingStandardsIgnoreEnd
                    }
                }
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        return count($collection);
    }

    /**
     * @param $hierarchyCode
     * @return int
     */
    public function caterHierarchyLeafRemoval($hierarchyCode)
    {
        $filters =  [['field' => 'main_table.HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq']];
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnlyWithAlias($filters, 100);
        $collection = $this->replHierarchyLeafCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'nav_id',
            'catalog_product_entity',
            'sku'
        );
        /** @var \Ls\Replication\Model\ReplHierarchyLeaf $hierarchyLeaf */
        foreach ($collection as $hierarchyLeaf) {
            try {
                $sku = $hierarchyLeaf->getNavId();
                $product = $this->productRepository->get($sku);
                $categories = $product->getCategoryIds();
                $categoryExistData = $this->isCategoryExist($hierarchyLeaf->getNodeId());
                if (!empty($categoryExistData)) {
                    $categoryId = $categoryExistData->getEntityId();
                    if (in_array($categoryId, $categories)) {
                        $this->categoryLinkRepositoryInterface->deleteByIds($categoryId, $sku);
                    }
                }
                $hierarchyLeaf->setData('is_processed', '1');
                $hierarchyLeaf->setData('IsDeleted', '0');
                $hierarchyLeaf->setData('is_updated', '0');
                // @codingStandardsIgnoreStart
                $this->replHierarchyLeafRepository->save($hierarchyLeaf);
                // @codingStandardsIgnoreEnd
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        return count($collection);
    }

    /**
     * @return array
     */
    public function executeManually()
    {
        $this->execute();
        $hierarchyCode = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE);
        $filters = [
            ['field' => 'ParentNode', 'value' => true, 'condition_type' => 'null'],
            ['field' => 'HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, 100);
        $replHierarchy = $this->replHierarchyNodeRepository->getList($criteria);
        $categoriesLeftToProcess = count($replHierarchy->getItems());
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
     * @return bool|\Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
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
        $imageSize = [
            'height' => $this->lsr::DEFAULT_IMAGE_HEIGHT,
            'width' => $this->lsr::DEFAULT_IMAGE_WIDTH
        ];
        /** @var \Ls\Omni\Client\Ecommerce\Entity\ImageSize $imageSizeObject */
        $imageSizeObject = $this->loyaltyHelper->getImageSize($imageSize);
        $result = $this->loyaltyHelper->getImageById($imageId, $imageSizeObject);
        if ($result instanceof \Ls\Omni\Client\Ecommerce\Entity\ImageView) {
            //check if directory exists or not and if it has the proper permission or not
            $offerpath = $this->getMediaPathtoStore();
            // @codingStandardsIgnoreStart
            if (!is_dir($offerpath)) {
                $this->file->mkdir($offerpath, 0775);
            }
            $format = strtolower($result->getFormat());
            $imageName = $this->oSlug($imageId);
            $output_file = "{$imageName}.$format";
            $file = "{$offerpath}{$output_file}";
            if (!$this->file->fileExists($file)) {
                $base64 = $result->getImage();
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
        $mediadirectory = $this->loyaltyHelper->getMediaPathtoStore();
        return $mediadirectory . "catalog" . DIRECTORY_SEPARATOR . "category" . DIRECTORY_SEPARATOR;
    }

    /**
     * Update/Add the modified/added images of the item
     */
    public function updateImagesOnly()
    {
        $filters = [
            ['field' => 'main_table.TableName', 'value' => 'Hierarchy Node', 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaGetUpdatedOnly($filters);
        $images = $this->replImageLinkRepositoryInterface->getList($criteria)->getItems();
        if (!empty($images)) {
            foreach ($images as $image) {
                try {
                    $keyValue = explode(',', $image->getKeyValue());
                    $navId = $keyValue[1];
                    $categoryExistData = $this->isCategoryExist($navId);
                    if ($categoryExistData) {
                        $imageSub = $this->getImage($image->getImageId());
                        $mediaAttribute = ['image', 'small_image', 'thumbnail'];
                        $categoryExistData->setImage($imageSub, $mediaAttribute, true, false);
                        // @codingStandardsIgnoreStart
                        $this->categoryRepository->save($categoryExistData);
                        $image->setData('is_updated', '0');
                        $this->replImageLinkRepositoryInterface->save($image);
                        // @codingStandardsIgnoreEnd
                        $this->cronStatus = true;
                    }
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                    continue;
                }
            }
        }
    }
}
