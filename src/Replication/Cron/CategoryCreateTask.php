<?php

namespace Ls\Replication\Cron;

use Ls\Replication\Model\ReplHierarchyRepository;
use Ls\Replication\Model\ReplHierarchyNodeRepository;
use Ls\Replication\Model\ReplHierarchyLeafRepository;
use Ls\Replication\Api\ReplImageLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Ls\Replication\Helper\ReplicationHelper;
use Psr\Log\LoggerInterface;
use Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Filesystem\Io\File;
use Ls\Core\Model\LSR;

class CategoryCreateTask
{

    /** @var CategoryFactory */
    protected $categoryFactory;

    /** @var CategoryRepositoryInterface */
    protected $categoryRepository;

    /** @var ReplHierarchyNodeRepository */
    protected $replHierarchyNodeRepository;

    /** @var ReplHierarchyLeafRepository */
    protected $replHierarchyLeafRepository;

    /** @var ReplImageLinkRepositoryInterface */
    protected $replImageLinkRepositoryInterface;

    /** @var LoggerInterface */
    protected $logger;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /* @var LoyaltyHelper */
    private $loyaltyHelper;

    /** @var ReplicationHelper */
    protected $replicationHelper;

    /* @var \Magento\Framework\Filesystem\Io\File $_file */
    protected $_file;

    /** @var LSR */
    protected $_lsr;

    /** @var Cron Checking */
    protected $cronStatus=false;

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
        LSR $LSR
    )
    {
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->replHierarchyNodeRepository = $replHierarchyNodeRepository;
        $this->replHierarchyLeafRepository = $replHierarchyLeafRepository;
        $this->replImageLinkRepositoryInterface = $replImageLinkRepositoryInterface;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->_file = $file;
        $this->replicationHelper = $replicationHelper;
        $this->_lsr = $LSR;
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $this->logger->debug("Running CategoryCreateTask");
        // for defning category images to the product group
        $mediaAttribute = array('image', 'small_image', 'thumbnail');
        $hierarchyCode = $this->_lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE);
        if (empty($hierarchyCode)) {
            $this->logger->debug("Hierarchy Code not defined in the configuration.");
            return;
        }
        $filters = array(
            array('field' => 'ParentNode', 'value' => true, 'condition_type' => 'null'),
            array('field' => 'HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq')
        );
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, 100);
        /** @var \Ls\Replication\Model\ReplHierarchyNodeSearchResults $replHierarchyNodeRepository */
        $replHierarchyNodeRepository = $this->replHierarchyNodeRepository->getList($criteria);
        /** @var \Ls\Replication\Model\ReplHierarchyNode $itemCategory */
        foreach ($replHierarchyNodeRepository->getItems() as $hierarchyNode) {
            if (empty($hierarchyNode->getNavId()))
                continue;
            $categoryExistData = $this->isCategoryExist($hierarchyNode->getNavId());
            if (!$categoryExistData) {
                /** @var \Magento\Catalog\Model\Category $category */
                $category = $this->categoryFactory->create();
                $data = [
                    'parent_id' => 2,
                    'name' => ($hierarchyNode->getDescription()) ? $hierarchyNode->getDescription() : $hierarchyNode->getNavId(),
                    'url_key' => $this->oSlug($hierarchyNode->getNavId()),
                    'is_active' => true,
                    'is_anchor' => false,
                    'include_in_menu' => true,
                    'meta_title' => ($hierarchyNode->getDescription()) ? $hierarchyNode->getDescription() : $hierarchyNode->getNavId(),
                    'nav_id' => $hierarchyNode->getNavId()
                ];
                $category->setData($data)->setAttributeSetId($category->getDefaultAttributeSetId());
                if ($hierarchyNode->getImageId()) {
                    $image = $this->getImage($hierarchyNode->getImageId());
                    $category->setImage($image, $mediaAttribute, true, false);
                }
                $this->categoryRepository->save($category);
                $hierarchyNode->setData('processed', '1');
                $hierarchyNode->save();
                $this->cronStatus=true;
            } else {
                if ($hierarchyNode->getIsUpdated() == 1) {
                    $categoryExistData->setData('name', ($hierarchyNode->getDescription()) ? $hierarchyNode->getDescription() : $hierarchyNode->getNavId());
                    if ($hierarchyNode->getImageId()) {
                        $image = $this->getImage($hierarchyNode->getImageId());
                        $categoryExistData->setImage($image, $mediaAttribute, true, false);
                    }
                    $this->categoryRepository->save($categoryExistData);
                    $hierarchyNode->setData('is_updated', '0');
                    $hierarchyNode->save();
                    $this->cronStatus=true;
                }
            }
        }
        // This is for the child/sub categories apply ParentNode Not Null Criteria
        $filtersSub = array(
            array('field' => 'ParentNode', 'value' => true, 'condition_type' => 'notnull'),
            array('field' => 'HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq')
        );
        $criteriaSub = $this->replicationHelper->buildCriteriaForArray($filtersSub, 100);
        /** @var \Ls\Replication\Model\ReplHierarchyNodeSearchResults $replHierarchyNodeRepositorySub */
        $replHierarchyNodeRepositorySub = $this->replHierarchyNodeRepository->getList($criteriaSub);
        foreach ($replHierarchyNodeRepositorySub->getItems() as $hierarchyNodeSub) {
            $itemCategoryId = $hierarchyNodeSub->getParentNode();
            $collection = $this->collectionFactory->create()
                ->addAttributeToFilter('nav_id', $itemCategoryId)
                ->setPageSize(1);
            $subCategoryExistData = $this->isCategoryExist($hierarchyNodeSub->getNavId());
            if ($collection->getSize() && !$subCategoryExistData) {
                $categorysub = $this->categoryFactory->create();
                $data = [
                    'parent_id' => $collection->getFirstItem()->getId(),
                    'name' => ($hierarchyNodeSub->getDescription()) ? $hierarchyNodeSub->getDescription() : $hierarchyNodeSub->getNavId(),
                    'url_key' => $this->oSlug($hierarchyNodeSub->getNavId()),
                    'is_active' => true,
                    'is_anchor' => true,
                    'include_in_menu' => true,
                    'meta_title' => ($hierarchyNodeSub->getDescription()) ? $hierarchyNodeSub->getDescription() : $hierarchyNodeSub->getNavId(),
                    'nav_id' => $hierarchyNodeSub->getNavId()
                ];
                $categorysub->setData($data)->setAttributeSetId($categorysub->getDefaultAttributeSetId());
                if ($hierarchyNodeSub->getImageId()) {
                    $imageSub = $this->getImage($hierarchyNodeSub->getImageId());
                    $categorysub->setImage($imageSub, $mediaAttribute, true, false);
                }
                $this->categoryRepository->save($categorysub);
                $hierarchyNodeSub->setData('processed', '1');
                $hierarchyNodeSub->save();
                    $this->cronStatus=true;
            } else {
                if ($hierarchyNodeSub->getIsUpdated() == 1) {
                    $subCategoryExistData->setData('name', ($hierarchyNodeSub->getDescription()) ? $hierarchyNodeSub->getDescription() : $hierarchyNodeSub->getNavId());
                    if ($hierarchyNodeSub->getImageId()) {
                        $imageSub = $this->getImage($hierarchyNodeSub->getImageId());
                        $subCategoryExistData->setImage($imageSub, $mediaAttribute, true, false);
                    }
                    $this->categoryRepository->save($subCategoryExistData);
                    $hierarchyNodeSub->setData('is_updated', '0');
                    $hierarchyNodeSub->save();
                    $this->cronStatus=true;
                }
            }
        }
        //Update the Modified Images 
        $this->updateImagesOnly();
        $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_CATEGORY);
    }

    /**
     * @param $string
     * @return string]
     */
    //TODO integrate existing slug check or check if the url already exist or not.
    protected function oSlug($string)
    {
        return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-',
            html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i',
                '$1', htmlentities($string, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
    }

    /**
     * Check if the category already exist or not
     * @param $nav_id
     * @return bool|\Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function isCategoryExist($nav_id)
    {
        $collection = $this->collectionFactory->create()
            ->addAttributeToFilter('nav_id', $nav_id)
            ->setPageSize(1);
        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }
        return false;
    }


    /**
     * @param string
     * @return \Ls\Omni\Client\Ecommerce\Entity\ImageGetByIdResponse|NULL
     */
    protected function getImage($imageId = '')
    {
        $imageSize = array(
            'height' => $this->_lsr::DEFAULT_IMAGE_HEIGHT,
            'width' => $this->_lsr::DEFAULT_IMAGE_WIDTH
        );
        /** @var \Ls\Omni\Client\Ecommerce\Entity\ImageSize $imageSizeObject */
        $imageSizeObject = $this->loyaltyHelper->getImageSize($imageSize);
        $result = $this->loyaltyHelper->getImageById($imageId, $imageSizeObject);
        if ($result instanceof \Ls\Omni\Client\Ecommerce\Entity\ImageView) {
            //check if directory exists or not and if it has the proper permission or not
            $offerpath = $this->getMediaPathtoStore();
            if (!is_dir($offerpath)) {
                $this->_file->mkdir($offerpath, 0775);
            }
            $format = strtolower($result->getFormat());
            $output_file = "{$imageId}.$format";
            $file = "{$offerpath}{$output_file}";
            if (!$this->_file->fileExists($file)) {
                $base64 = $result->getImage();
                $image_file = fopen($file, 'wb');
                fwrite($image_file, base64_decode($base64));
                fclose($image_file);
            }
            $image = "{$output_file}";
        }
        return $image;
    }

    /**
     * Return the media path of the category
     * @return string
     */
    protected function getMediaPathtoStore()
    {
        $mediadirectory = $this->loyaltyHelper->getMediaPathtoStore();
        return $mediadirectory . "catalog" . DIRECTORY_SEPARATOR . "category" . DIRECTORY_SEPARATOR;
    }


    /**
     * Update/Add the modified/added images of the item
     */
    protected function updateImagesOnly()
    {
        $filters = array(
            array('field' => 'TableName', 'value' => 'Hierarchy Node', 'condition_type' => 'eq')
        );
        $criteria = $this->replicationHelper->buildCriteriaGetUpdatedOnly($filters);
        $images = $this->replImageLinkRepositoryInterface->getList($criteria)->getItems();
        if (count($images) > 0) {
            foreach ($images as $image) {
                try {
                    $keyValue = explode(',', $image->getKeyValue());
                    $navId = $keyValue[1];
                    $categoryExistData = $this->isCategoryExist($navId);
                    if ($categoryExistData) {
                        $imageSub = $this->getImage($image->getImageId());
                        $mediaAttribute = array('image', 'small_image', 'thumbnail');
                        $categoryExistData->setImage($imageSub, $mediaAttribute, true, false);
                        $this->categoryRepository->save($categoryExistData);
                        $image->setData('is_updated', '0');
                        $image->save();
                        $this->cronStatus=true;
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    continue;
                }
            }
        }
    }
}
