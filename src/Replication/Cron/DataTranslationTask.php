<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplDataTranslation;
use \Ls\Replication\Api\ReplDataTranslationRepositoryInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ResourceModel\ReplDataTranslation\CollectionFactory as ReplDataTranslationCollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class DataTranslationTask
 * @package Ls\Replication\Cron
 */
class DataTranslationTask
{

    /**
     * @var ReplicationHelper
     */
    public $replicationHelper;

    /**
     * @var ReplDataTranslationRepositoryInterface
     */
    public $dataTranslationRepository;

    /**
     * @var CategoryCollectionFactory
     */
    public $categoryCollectionFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    public $categoryRepository;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var StoreInterface $store
     */
    public $store;

    /**
     * @var bool
     */
    public $cronStatus = false;

    /** @var Attribute */
    public $eavAttribute;

    /**
     * @var ReplDataTranslationCollectionFactory
     */
    public $replDataTranslationCollectionFactory;

    /**
     * DataTranslationTask constructor.
     * @param ReplicationHelper $replicationHelper
     * @param ReplDataTranslationRepositoryInterface $dataTranslationRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param LSR $LSR
     * @param Logger $logger
     * @param ReplDataTranslationCollectionFactory $replDataTranslationCollectionFactory
     * @param Attribute $eavAttribute
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        ReplDataTranslationRepositoryInterface $dataTranslationRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        LSR $LSR,
        Logger $logger,
        ReplDataTranslationCollectionFactory $replDataTranslationCollectionFactory,
        Attribute $eavAttribute
    ) {
        $this->replicationHelper                    = $replicationHelper;
        $this->dataTranslationRepository            = $dataTranslationRepository;
        $this->categoryCollectionFactory            = $categoryCollectionFactory;
        $this->categoryRepository                   = $categoryRepository;
        $this->lsr                                  = $LSR;
        $this->logger                               = $logger;
        $this->replDataTranslationCollectionFactory = $replDataTranslationCollectionFactory;
        $this->eavAttribute                         = $eavAttribute;
    }

    /**
     * @param null $storeData
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function execute($storeData = null)
    {
        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            $stores = $this->lsr->getAllStores();
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                $langCode    = $this->lsr->getStoreConfig(LSR::SC_STORE_DATA_TRANSLATION_LANG_CODE, $store->getId());
                if (!empty($langCode)) {
                    $this->logger->debug('DataTranslationTask Started for Store ' . $store->getName());
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_CRON_DATA_TRANSLATION_TO_MAGENTO_CONFIG_PATH_LAST_EXECUTE,
                        $store->getId()
                    );
                    $this->updateHierarchyNode($store->getId(), $langCode);
                    $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO,
                        $store->getId());
                    $this->logger->debug('DataTranslationTask Completed for Store ' . $store->getName());
                }
                $this->lsr->setStoreId(null);
            }
        }
    }


    /**
     * @param null $storeData
     * @return int[]
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        return [0];
    }

    /**
     * @param $storeId
     * @param $langCode
     * @throws LocalizedException
     * @throws CouldNotSaveException
     */
    public function updateHierarchyNode($storeId, $langCode)
    {
        $attribute_id = $this->eavAttribute->getIdByCode(Category::ENTITY, 'nav_id');
        $filters      = [
            ['field' => 'main_table.scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'main_table.LanguageCode', 'value' => $langCode, 'condition_type' => 'eq'],
            ['field' => 'main_table.TranslationId', 'value' => LSR::SC_TRANSACTION_ID_HIERARCHY_NODE, 'condition_type' => 'eq'],
            ['field' => 'main_table.key', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'second.attribute_id', 'value' => $attribute_id, 'condition_type' => 'eq'],
            ['field' => 'second.store_id', 'value' => $storeId, 'condition_type' => 'eq'],
        ];
        $criteria     = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, -1);
        $collection = $this->replDataTranslationCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'key',
            'catalog_category_entity_varchar',
            'value'
        );
        /** @var ReplDataTranslation $dataTranslation */
        foreach ($collection as $dataTranslation) {
            try {
                $categoryExistData = $this->isCategoryExist($dataTranslation->getKey(), true);
                if ($categoryExistData) {
                    $categoryExistData->setData('name', $dataTranslation->getText());
                    // @codingStandardsIgnoreLine
                    $this->categoryRepository->save($categoryExistData);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->logger->debug('Error while saving data translation ' . $dataTranslation->getKey());
                $dataTranslation->setData('is_failed', 1);
            }
            $dataTranslation->setData('processed_at', $this->replicationHelper->getDateTime());
            $dataTranslation->setData('processed', 1);
            $dataTranslation->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->dataTranslationRepository->save($dataTranslation);
        }
        if ($collection->getSize() == 0) {
            $this->cronStatus = true;
        }
    }

    /**
     * Check if the category already exist or not
     * @param $navId
     * @param false $store
     * @return false|DataObject
     * @throws LocalizedException
     */
    public function isCategoryExist($navId, $store = false)
    {
        $collection = $this->categoryCollectionFactory->create()->addAttributeToFilter('nav_id', $navId);
        if ($store) {
            $collection->addPathsFilter('1/' . $this->store->getRootCategoryId() . '/');
        }
        $collection->setPageSize(1);
        if ($collection->getSize()) {
            // @codingStandardsIgnoreStart
            return $collection->getFirstItem();
            // @codingStandardsIgnoreEnd
        }
        return false;
    }
}
