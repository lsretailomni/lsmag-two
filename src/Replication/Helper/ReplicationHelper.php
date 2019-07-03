<?php

namespace Ls\Replication\Helper;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Replication\Api\ReplImageLinkRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Class ReplicationHelper
 * @package Ls\Replication\Helper
 */
class ReplicationHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    /** @var \Magento\Store\Model\StoreManagerInterface */
    public $storeManager;

    /** @var \Magento\Framework\Filesystem */
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

    /** @var cron config save */
    public $configWriter;

    /** @var Set */
    public $attributeSet;

    /** @var chache type list */
    public $cacheTypeList;

    /** @var LSR */
    public $lsr;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;

    /**
     * ReplicationHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $Filesystem
     * @param Config $eavConfig
     * @param WriterInterface $configWriter
     * @param Set $attributeSet
     * @param TypeListInterface $cacheTypeList
     * @param LSR $LSR
     * @param ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $Filesystem,
        Config $eavConfig,
        WriterInterface $configWriter,
        Set $attributeSet,
        TypeListInterface $cacheTypeList,
        LSR $LSR,
        ResourceConnection $resource
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->storeManager = $storeManager;
        $this->filesystem = $Filesystem;
        $this->replImageLinkRepositoryInterface = $replImageLinkRepositoryInterface;
        $this->eavConfig = $eavConfig;
        $this->configWriter = $configWriter;
        $this->attributeSet = $attributeSet;
        $this->cacheTypeList = $cacheTypeList;
        $this->lsr = $LSR;
        $this->resource = $resource;
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
     * @return \Magento\Framework\Api\SearchCriteria
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
     * @param string $item_id
     * @param int $pagesize
     * @param bool $excludeDeleted
     * @return \Magento\Framework\Api\SearchCriteria
     */
    public function buildCriteriaForProductAttributes($item_id = '', $pagesize = 100, $excludeDeleted = true)
    {
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
        $criteria->addFilter('LinkType', 0, 'eq');
        $criteria->addFilter('LinkField1', $item_id, 'eq');
        if ($excludeDeleted) {
            $criteria->addFilter('IsDeleted', 0, 'eq');
        }
        $criteria->setPageSize($pagesize);
        return $criteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters
     * @param array $filters
     * @param int $pagesize
     * @param boolean $excludeDeleted
     * @return \Magento\Framework\Api\SearchCriteria
     */
    public function buildCriteriaForArray(array $filters, $pagesize = 100, $excludeDeleted = true)
    {
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
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $criteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        if ($excludeDeleted) {
            $criteria->addFilter('IsDeleted', 0, 'eq');
        }
        $criteria->setPageSize($pagesize);
        return $criteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters
     * @param array $filters
     * @param int $pagesize
     * @param boolean $excludeDeleted
     * @return \Magento\Framework\Api\SearchCriteria
     */
    public function buildCriteriaForDirect(array $filters, $pagesize = 100, $excludeDeleted = true)
    {
        $criteria = $this->searchCriteriaBuilder->setFilterGroups([]);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $criteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        if ($excludeDeleted) {
            $criteria->addFilter('IsDeleted', 0, 'eq');
        }
        $criteria->setPageSize($pagesize);
        return $criteria->create();
    }
    /**
     * Create Build Criteria with Array of filters as a parameters and return Updated Only
     * @param array $filters
     * @param int $pagesize
     * @return \Magento\Framework\Api\SearchCriteria
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
        $criteria->setPageSize($pagesize);
        return $criteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters and return Updated Only
     * @param array $filters
     * @param int $pagesize
     * @return \Magento\Framework\Api\SearchCriteria
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
        $criteria->setPageSize($pagesize);
        return $criteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters and return Updated Only
     * @param array $filters
     * @param int $pagesize
     * @return \Magento\Framework\Api\SearchCriteria
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
        $criteria->setPageSize($pagesize);
        return $criteria->create();
    }
    /**
     * Create Build Exit Criteria with Array of filters as a parameters
     * @param array $filters
     * @param int $pagesize
     * @return \Magento\Framework\Api\SearchCriteria
     */
    public function buildExitCriteriaForArray(array $filters, $pagesize = 1)
    {
        $searchCriteria = $this->searchCriteriaBuilder;
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $searchCriteria->addFilter($filter['field'], $filter['value'], $filter['condition_type']);
            }
        }
        $searchCriteria->setPageSize($pagesize);
        return $searchCriteria->create();
    }

    /**
     * Create Build Criteria with Array of filters as a parameters
     * @param array $filters
     * @param int $pagesize
     * @param boolean $excludeDeleted
     * @return \Magento\Framework\Api\SearchCriteria
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
        $criteria->setPageSize($pagesize);
        return $criteria->create();
    }

    /**
     * @param string $nav_id
     * @param string $type
     * @return bool|\Magento\Framework\Api\AbstractExtensibleObject[]
     */
    public function getImageLinksByType($nav_id = '', $type = 'Item Category')
    {
        //first and the most important condition
        if ($nav_id == '' || $nav_id === null) {
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
            0,
            'eq'
        )->create();

        /** @var \Ls\Replication\Model\ReplImageLinkSearchResults $items */
        $items = $this->replImageLinkRepositoryInterface->getList($criteria);
        if ($items->getTotalCount() > 0) {
            return $items->getItems();
        }
        return false;
    }

    /**
     * @param string $image_id
     * @return Entity\ImageStreamGetByIdResponse|\Ls\Omni\Client\ResponseInterface|null|string
     */
    public function imageStreamById($image_id = '')
    {
        $response = null;
        if ($image_id == '' || $image_id == null) {
            return $response;
        }
        // @codingStandardsIgnoreStart
        $request = new Operation\ImageStreamGetById();
        $entity = new Entity\ImageStreamGetById();
        // @codingStandardsIgnoreEnd
        $entity->setId($image_id);
        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * @return null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDefaultAttributeSetId()
    {
        return $this->eavConfig->getEntityType('catalog_product')
            ->getDefaultAttributeSetId();
    }

    /**
     * @param string $attributeset
     * @return int|null
     */
    public function getDefaultGroupIdOfAttributeSet($attributeset = '')
    {
        if ($attributeset == '') {
            $attributeset = 4;
        }
        return $this->attributeSet->getDefaultGroupId($attributeset);
    }

    /**
     * Format the Nav attribute code according to Magento without space and lowercase
     * @param $code
     * @return mixed|string
     */
    public function formatAttributeCode($code)
    {
        $code = strtolower(trim($code));
        $code = str_replace(" ", "_", $code);
        return 'ls_' . $code;
    }

    /**
     * @return array
     */
    public function getAllWebsitesIds()
    {
        $websiteIds = [];
        $websites = $this->storeManager->getWebsites();
        /** @var \Magento\Store\Model\Website\Interceptor $website */
        foreach ($websites as $website) {
            $websiteIds[] = $website->getId();
        }
        return $websiteIds;
    }

    /**
     * Clear the cache for type config
     */
    public function flushConfig()
    {
        $this->cacheTypeList->cleanType('config');
        $this->_logger->debug('Config Flushed');
    }

    /**
     * Update the config status and clean cache for config
     * @param $data
     * @param $path
     */
    public function updateCronStatus($data, $path)
    {
        $this->configWriter->save(
            $path,
            ($data) ? 1 : 0,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->flushConfig();
    }

    /**
     * Update the config value
     * @param $value
     * @param $path
     */
    public function updateConfigValue($value, $path)
    {
        $this->configWriter->save(
            $path,
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Trigger the disposable Hierarchy replication job to get Hierarchy based on stores.
     * @return array|Entity\ReplEcommHierarchyResponse|Entity\ReplHierarchyResponse|\Ls\Omni\Client\ResponseInterface
     */
    public function getHierarchyByStore()
    {
        $response = [];
        $store_id = $this->lsr->getDefaultWebStore();
        // @codingStandardsIgnoreStart
        /** @var Entity\ReplEcommHierarchy $hierarchy */
        $hierarchy = new Entity\ReplEcommHierarchy();

        /** @var  Entity\ReplRequest $request */
        $request = new Entity\ReplRequest();

        /** @var Operation\ReplEcommHierarchy $operation */
        $operation = new Operation\ReplEcommHierarchy();
        // @codingStandardsIgnoreEnd

        $request->setStoreId($store_id)
            ->setBatchSize(100)
            ->setFullReplication(true)
            ->setLastKey(0)
            ->setMaxKey(0)
            ->setTerminalId('');

        $this->_logger->debug(var_export($operation->getResponse(), true));

        try {
            $response = $operation->execute($hierarchy->setReplRequest($request));
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * @param $collection
     * @param SearchCriteriaInterface $criteria
     * @param $primaryTableColumnName
     * @param $secondaryTableName
     * @param $secondaryTableColumnName
     */
    public function setCollectionPropertiesPlusJoin(
        &$collection,
        SearchCriteriaInterface $criteria,
        $primaryTableColumnName,
        $secondaryTableName,
        $secondaryTableColumnName
    ) {
        foreach ($criteria->getFilterGroups() as $filter_group) {
            $fields = [];
            $conditions = [];
            foreach ($filter_group->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
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
        $second_table_name = $this->resource->getTableName($secondaryTableName);
        // @codingStandardsIgnoreStart
        // In order to only select those records whose items are available
        $collection->getSelect()->joinInner(
            array('second' => $second_table_name),
            'main_table.' . $primaryTableColumnName . ' = second.'.$secondaryTableColumnName,
            []
        );
        // @codingStandardsIgnoreEnd
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
    }
}
