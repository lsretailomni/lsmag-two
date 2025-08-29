<?php

namespace Ls\Replication\Cron;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\Data as LsHelper;
use \Ls\Core\Model\LSR;
use Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountValueType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\HierarchyType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscMemberType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountType;
use Ls\Omni\Client\Ecommerce\Entity\HierarchyView;
use Ls\Omni\Client\Ecommerce\Entity\LSCWIItemBuffer;
use Ls\Omni\Client\Ecommerce\Entity\PeriodicDiscView;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Abstract replication class for all
 * the flat tables
 */
abstract class AbstractReplicationTask
{
    /** @var array All those config path don't have no lastkey means always zero as LastKey */
    private static $no_lastkey_config_path = [
        'ls_mag/replication/repl_country_code',
        'ls_mag/replication/repl_shipping_agent',
        'ls_mag/replication/repl_store_tender_type',
        'ls_mag/replication/repl_inv_status'
    ];

    /** @var Logger */
    public $logger;
    /** @var ScopeConfigInterface */
    public $scope_config;
    /** @var Config */
    public $resource_config;
    /** @var LsHelper */
    public $ls_helper;
    /** @var null */
    public $properties = null;
    /** @var ReplicationHelper */
    public $rep_helper;
    /** @var integer */
    public $recordsRemaining = 0;
    /** @var bool */
    public $cronStatus = false;

    public $defaultScope = ScopeInterface::SCOPE_WEBSITES;

    /**
     * @param ScopeConfigInterface $scope_config
     * @param Config $resource_config
     * @param Logger $logger
     * @param LsHelper $helper
     * @param ReplicationHelper $repHelper
     */
    public function __construct(
        ScopeConfigInterface $scope_config,
        Config $resource_config,
        Logger $logger,
        LsHelper $helper,
        ReplicationHelper $repHelper
    ) {
        $this->scope_config    = $scope_config;
        $this->resource_config = $resource_config;
        $this->logger          = $logger;
        $this->ls_helper       = $helper;
        $this->rep_helper      = $repHelper;
        $this->setDefaultScope();
    }

    /**
     * Entry point for cron jobs
     *
     * @param mixed $storeData
     * @return void
     * @throws NoSuchEntityException|GuzzleException
     */
    public function execute($storeData = null)
    {
        if ($this->defaultScope == ScopeInterface::SCOPE_WEBSITES ||
            $this->defaultScope == ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        ) {
            $lsr = $this->getLsrModel();

            if (!$lsr->isSSM()) {
                if (!empty($storeData) && $storeData instanceof WebsiteInterface) {
                    $stores = [$storeData];
                } else {
                    $stores = $this->getAllWebsites();
                }
            } else {
                $stores = [$lsr->getAdminStore()];
            }

            if (!empty($stores)) {
                foreach ($stores as $store) {
                    if ($this->getLsrModel()->isEnabled($store->getId(), $this->defaultScope)) {
                        if ($this->executeDiscountReplicationOnCentralType($lsr, $store, $this->defaultScope)) {
                            continue;
                        }
                        $this->fetchDataGivenStore($store->getId());
                    }
                }
            }
        } else {
            /**
             * Get all the available stores config in the Magento system
             */
            if (!empty($storeData) && $storeData instanceof StoreInterface) {
                $stores = [$storeData];
            } else {
                /** @var StoreInterface[] $stores */
                $stores = $this->getAllStores();
            }
            if (!empty($stores)) {
                foreach ($stores as $store) {
                    if ($this->getLsrModel()->isEnabled($store->getId())) {
                        $this->fetchDataGivenStore($store->getId());
                    }
                }
            }
        }
    }

    /**
     * Entry point for the cron when running manually from admin
     *
     * @param $storeData
     * @return int[]
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        return [$this->recordsRemaining];
    }

    /**
     * Update the Custom Replication Success Status
     *
     * @param bool $storeId
     */
    public function updateSuccessStatus($storeId = false)
    {
        $confPath = $this->getConfigPath();

        if ($confPath == ReplLscAttributeTask::CONFIG_PATH ||
            $confPath == ReplLscAttributeOptionValueTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ATTRIBUTE);
        } elseif ($confPath == ReplLscWiExtdVariantValuesTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT);
        } elseif ($confPath == ReplItemVariantTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT);
        } elseif ($confPath == ReplHierarchynodesviewTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_CATEGORY);
        } elseif ($confPath == ReplPeriodicdiscviewTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP);
        } elseif ($confPath == ReplLscValidationPeriodTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_DISCOUNT_VALIDATION);
        } elseif ($confPath == ReplLscWiItemBufferTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_PRODUCT);
        } elseif ($confPath == ReplHierarchynodeslinkviewTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ITEM_UPDATES);
        } elseif ($confPath == ReplVendorTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_VENDOR);
        } elseif ($confPath == ReplVendoritemviewTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_VENDOR_ATTRIBUTE);
        }
    }

    /**
     * Update all dependent flat to magento crons status
     *
     * @param $websiteId
     * @param $path
     * @return void
     */
    public function updateAllStoresConfigs($websiteId, $path)
    {
        foreach ($this->getAllStores() as $store) {
            if ($store->getWebsiteId() == $websiteId) {
                $this->rep_helper->updateCronStatus(
                    false,
                    $path,
                    ($store->getId()) ?: false,
                    false,
                    ScopeInterface::SCOPE_STORES
                );
            }
        }
    }

    /**
     * Save new source or update already existing source
     *
     * @param array $properties
     * @param mixed $source
     * @throws Exception
     */
    public function saveSource($properties, $source)
    {
        $isDeleted = 0;
        if ($source->getIsDeleted()) {
            $uniqueAttributes = (array_key_exists(
                $this->getConfigPath(),
                ReplicationHelper::DELETE_JOB_CODE_UNIQUE_FIELD_ARRAY
            )) ?
                ReplicationHelper::DELETE_JOB_CODE_UNIQUE_FIELD_ARRAY[$this->getConfigPath()] :
                ReplicationHelper::JOB_CODE_UNIQUE_FIELD_ARRAY[$this->getConfigPath()];
            $isDeleted = 1;
        } else {
            $uniqueAttributes = ReplicationHelper::JOB_CODE_UNIQUE_FIELD_ARRAY[$this->getConfigPath()];
        }
        $confPath = $this->getConfigPath();
        if ($confPath == "ls_mag/replication/repl_discount_validation") {
            $source->setStartDate($this->rep_helper->convertDateTimeIntoCurrentTimeZone(
                $source->getStartDate(),
                LSR::DATE_FORMAT,
                false
            ));

            $source->setStartTime($this->rep_helper->convertDateTimeIntoCurrentTimeZone(
                $source->getStartTime(),
                LSR::TIME_FORMAT,
                false
            ));
            $source->setEndDate($this->rep_helper->convertDateTimeIntoCurrentTimeZone(
                $source->getEndDate(),
                LSR::DATE_FORMAT,
                false
            ));

            $source->setEndTime($this->rep_helper->convertDateTimeIntoCurrentTimeZone(
                $source->getEndTime(),
                LSR::TIME_FORMAT,
                false
            ));
        }

        if ($confPath == ReplHierarchyviewTask::CONFIG_PATH ||
            $confPath == ReplHierarchynodeslinkviewTask::CONFIG_PATH
        ) {
            $value = $this->getConstantByIndex(HierarchyType::class, $source->getData(HierarchyView::TYPE));
            $source->setData(HierarchyView::TYPE, $value);
        } elseif ($confPath == ReplPeriodicdiscviewTask::CONFIG_PATH) {
            $value1 = $this->getConstantByIndex(
                DiscountValueType::class,
                $source->getData(PeriodicDiscView::DISCOUNT_TYPE)
            );
            $value2 = $this->getConstantByIndex(
                ReplDiscMemberType::class,
                $source->getData(PeriodicDiscView::MEMBER_TYPE)
            );
            $value3 = $this->getConstantByIndex(
                ReplDiscountType::class,
                $source->getData(PeriodicDiscView::TYPE)
            );
            $source->setData(PeriodicDiscView::DISCOUNT_TYPE, $value1);
            $source->setData(PeriodicDiscView::MEMBER_TYPE, $value2);
            $source->setData(PeriodicDiscView::TYPE, $value3);
        } elseif ($confPath == ReplLscWiItemBufferTask::CONFIG_PATH) {
            if (!empty($source->getData(LSCWIItemBuffer::ITEM_HTML))) {
                $source->setData(
                    LSCWIItemBuffer::ITEM_HTML,
                    base64_decode($source->getData(LSCWIItemBuffer::ITEM_HTML))
                );
            }
        }
        $checksum             = $this->getHashGivenString($source->getData());
        $uniqueAttributesHash = $this->generateIdentityValue($uniqueAttributes, $source, $properties);
        $entityArray          = $this->checkEntityExistByAttributes(
            $uniqueAttributes,
            $source,
            $uniqueAttributesHash,
            $properties
        );

        if (!empty($entityArray)) {
            $entity = reset($entityArray);
            $entity->setIsUpdated(1);
            $entity->setIsFailed(0);
            $entity->setUpdatedAt($this->rep_helper->getDateTime());
            $entity->setIdentityValue($uniqueAttributesHash);
        } else {
            $entity = $this->getFactory()->create();
        }

        $entity->setData('IsDeleted', $isDeleted);

        if ($entity->getChecksum() != $checksum) {
            $entity->addData(
                [
                    'checksum' => $checksum,
                    'identity_value' => $uniqueAttributesHash,
                    'scope' => $source->getScope(),
                    'scope_id' => $source->getScopeId()
                ]
            );
            foreach ($properties as $propertyIndex => $property) {
                $entity->setData($property, $source->getData($propertyIndex));
            }
        }
        try {
            $this->getRepository()->save($entity);
            $entity->setData([]);
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Get constant name by index
     *
     * @param string $class
     * @param int $index
     * @return string|null
     * @throws \ReflectionException
     */
    public function getConstantByIndex(string $class, int $index): ?string
    {
        $reflection = new \ReflectionClass($class);

        // Get constants in the order they are defined
        $constants = $reflection->getReflectionConstants();

        // Build ordered list [ "ITEM_DEAL" => "ItemDeal", ... ]
        $orderedConstants = [];
        foreach ($constants as $constant) {
            $orderedConstants[$constant->getName()] = $constant->getValue();
        }

        // Get by numeric index
        $values = array_values($orderedConstants);
        return $values[$index] ?? null;
    }

    /**
     * Get Properties
     *
     * @return string[]
     */
    public function getProperties()
    {
        if ($this->properties == null) {
            // @codingStandardsIgnoreStart
            $this->properties = $this->getMainEntity()::getDbColumnsMapping();
            // @codingStandardsIgnoreEnd
        }
        return $this->properties;
    }

    /**
     * Check the Entity exist or not
     *
     * @param array $uniqueAttributes
     * @param $source
     * @param string $uniqueAttributesHash
     * @param array $properties
     * @return mixed
     */
    public function checkEntityExistByAttributes(
        array $uniqueAttributes,
        $source,
        string $uniqueAttributesHash,
        array $properties
    ) {
        $criteria = $this->getSearchCriteria();
        $criteria->addFilter(ReplicationHelper::UNIQUE_HASH_COLUMN_NAME, $uniqueAttributesHash);

        $result = $this->getRepository()->getList($criteria->create())->getItems();

        if (empty($result)) {
            $criteria = $this->getSearchCriteria();

            foreach ($uniqueAttributes as $index => $attribute) {
                $key = array_search($index, $properties);

                if ($key === false) {
                    $key = $index;
                }

                $sourceValue = $source->getData($key);

                if ($sourceValue == "") {
                    $criteria->addFilter($attribute, true, 'null');
                } else {
                    $criteria->addFilter($attribute, $sourceValue);
                }
            }

            $result = $this->getRepository()->getList($criteria->create())->getItems();
        }

        return $result;
    }

    /**
     * Get search criteria
     *
     * @return mixed
     */
    public function getSearchCriteria()
    {
        $objectManager = $this->getObjectManager();
        // @codingStandardsIgnoreStart
        return $objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
        // @codingStandardsIgnoreEnd
    }

    /**
     * Generate identity value, a hash string to uniquely identify a record
     *
     * @param array $uniqueAttributes
     * @param mixed $source
     * @param array $properties
     * @return int
     */
    public function generateIdentityValue($uniqueAttributes, $source, $properties)
    {
        $uniqueAttributesHash = [];
        $i = 0;
        foreach ($uniqueAttributes as $index => $attribute) {
            $key = array_search($index, $properties);

            if (!$key) {
                $sourceValue = $source->getData($index);
            } else {
                $sourceValue = $source->getData($key);
            }

            $uniqueAttributesHash[] = ($sourceValue !== "" ? $sourceValue : $index) . '#' . $i;
            $i++;
        }

        $uniqueAttributesHash = implode("$", $uniqueAttributesHash);

        return $this->getHashGivenString($uniqueAttributesHash);
    }

    /**
     * Get Attribute value
     *
     * @param string $attribute
     * @param mixed $source
     * @return mixed
     */
    public function getAttributeValue($attribute, $source)
    {
        $fieldNameCapitalized = str_replace(' ', '', ucwords(str_replace('_', ' ', $attribute)));

        if ($attribute == 'nav_id') {
            $getMethod = 'getId';
        } else {
            $getMethod = "get$fieldNameCapitalized";
        }

        return $source->{$getMethod}();
    }

    /**
     * Get hash given string
     *
     * @param string $value
     * @return int
     */
    public function getHashGivenString($value)
    {
        // phpcs:ignore Magento2.Security.InsecureFunction
        return crc32(serialize($value));
    }

    /**
     * Check LastKey is always zero or not using Replication Config Path
     *
     * @param $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isLastKeyAlwaysZero($storeId)
    {
        $lsrModel = $this->getLsrModel();
        $noLastKeyConfigPaths = self::$no_lastkey_config_path;

        if (version_compare(
            $lsrModel->getOmniVersion($storeId, $this->defaultScope),
            '2024.4.0',
            '>='
        )) {
            $noLastKeyConfigPaths = self::$no_lastkey_config_path;

            if (($key = array_search('ls_mag/replication/repl_inv_status', $noLastKeyConfigPaths)) !== false) {
                unset($noLastKeyConfigPaths[$key]);
            }
        }

        return in_array($this->getConfigPath(), $noLastKeyConfigPaths);
    }

    /**
     * Get last key
     *
     * @param $storeId
     * @return mixed|null
     */
    public function getLastKey($storeId)
    {
        $lsrModel = $this->getLsrModel();

        return $lsrModel->getConfigValueFromDb(
            $this->getConfigPath(),
            $this->defaultScope,
            $storeId
        );
    }

    /**
     * Get last entry no
     *
     * @param $storeId
     * @return mixed|null
     */
    public function getLastEntryNo($storeId)
    {
        $lsrModel = $this->getLsrModel();

        return $lsrModel->getConfigValueFromDb(
            $this->getConfigPathLastEntryNo(),
            $this->defaultScope,
            $storeId
        );
    }

    /**
     * Check to see if running first time
     *
     * @param $storeId
     * @return mixed|null
     */
    public function isFirstTime($storeId)
    {
        $lsrModel = $this->getLsrModel();

        return $lsrModel->getConfigValueFromDb(
            $this->getConfigPathStatus(),
            $this->defaultScope,
            $storeId
        );
    }

    /**
     * Persist last key
     *
     * @param $lastKey
     * @param $storeId
     * @return void
     */
    public function persistLastKey($lastKey, $storeId)
    {
        $this->rep_helper->updateConfigValue($lastKey, $this->getConfigPath(), $storeId, $this->defaultScope);
    }

    /**
     * Persist last entry no
     *
     * @param string $lastEntryNo
     * @param string $storeId
     * @return void
     */
    public function persistLastEntryNo($lastEntryNo, $storeId)
    {
        $this->rep_helper->updateConfigValue(
            $lastEntryNo,
            $this->getConfigPathLastEntryNo(),
            $storeId,
            $this->defaultScope
        );
    }

    /**
     * We cant use the DI method to get LSR model in here,
     *
     * So we need to use the object manager approach to get LSR model.
     *
     * @return LSR
     */
    public function getLsrModel()
    {
        $objectManager = $this->getObjectManager();
        // @codingStandardsIgnoreStart
        return $objectManager->get(LSR::class);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Better to use this function when we need Object Manger in order to Organize all code in single place.
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return ObjectManager::getInstance();
    }

    /**
     * Get all stores
     *
     * @return StoreInterface[]
     */
    public function getAllStores()
    {
        return $this->getObjectManager()->get(StoreManagerInterface::class)->getStores();
    }

    /**
     * Get all websites
     *
     * @return StoreInterface[]
     */
    public function getAllWebsites()
    {
        return $this->getObjectManager()->get(StoreManagerInterface::class)->getWebsites();
    }

    /**
     * Get Batch Size
     *
     * @param $lsr
     * @param $storeId
     * @return int|string
     */
    public function getBatchSize($lsr, $storeId)
    {
        $batchSize      = 100;
        $isBatchSizeSet = $lsr->getStoreConfig(
            LSR::SC_REPLICATION_DEFAULT_BATCHSIZE
        );
        if ($isBatchSizeSet && is_numeric($isBatchSizeSet)) {
            $batchSize = $isBatchSizeSet;
        }

        return $batchSize;
    }

    /**
     * Get WebStore ID
     *
     * @param $lsr
     * @param $storeId
     * @return string
     */
    public function getWebStoreId($lsr, $storeId)
    {
        return $lsr->getGivenConfigInGivenScope(
            LSR::SC_SERVICE_STORE,
            $this->defaultScope,
            $storeId
        );
    }

    /**
     * Get WebStore ID
     *
     * @param $lsr
     * @param $storeId
     * @return string
     */
    public function getBaseUrl($lsr, $storeId)
    {
        return $lsr->getGivenConfigInGivenScope(
            LSR::SC_SERVICE_BASE_URL,
            $this->defaultScope,
            $storeId
        );
    }

    /**
     * This function is overriding in commerce cloud module
     *
     * Get full replication and app Id
     *
     * @param LSR $lsr
     * @param string $storeId
     * @return array
     */
    public function getRequiredParamsForMakingRequest($lsr, $storeId)
    {
        $lastKey    = $this->getLastKey($storeId);
        $lastEntryNo = $this->getLastEntryNo($storeId);
        $batchSize  = $this->getBatchSize($lsr, $storeId);
        $webStoreID = $this->getWebStoreId($lsr, $storeId);
        $baseUrl    = $this->getBaseUrl($lsr, $storeId);

        return [$lastKey ?? '', true, $batchSize, $webStoreID, $lastEntryNo ?? 0, $baseUrl, ''];
    }

    /**
     * Make request Fetch Data for given store
     *
     * @param $storeId
     * @throws NoSuchEntityException|GuzzleException
     */
    public function fetchDataGivenStore($storeId)
    {
        $lsr = $this->getLsrModel();
        $currentStoreId = $lsr->getStoreManagerObject()->getStore()->getId();
        $lsr->setStoreId($storeId);

        // Need to check if is_lsr is enabled on each store and only process the relevant store.
        if ($lsr->isLSR($storeId, $this->defaultScope)) {
            $this->rep_helper->updateConfigValue(
                $this->rep_helper->getDateTime(),
                $this->getConfigPathLastExecute(),
                $storeId,
                $this->defaultScope
            );

            list($lastKey, $fullRepl, $batchSize, $webStoreID, $lastEntryNo, $baseUrl) =
                $this->getRequiredParamsForMakingRequest($lsr, $storeId);

            $isFirstTime = $this->isFirstTime($storeId);
            if (isset($isFirstTime) && $isFirstTime == 1) {
                $fullRepl = false;

                if ($this->isLastKeyAlwaysZero($storeId)) {
                    return;
                }
            }

            $request = $this->makeRequest(
                '',
                [],
                '',
                $fullRepl,
                $batchSize,
                $webStoreID,
                $lastEntryNo,
                $lastKey
            );

            $this->processResponseGivenRequest($request, $storeId);
        } else {
            $this->logger->debug('LS Retail validation failed for store id ' . $storeId);
        }

        $lsr->setStoreId($currentStoreId);
    }

    /**
     * Use given request and save response
     *
     * @param $request
     * @param $storeId
     */
    public function processResponseGivenRequest($request, $storeId)
    {
        try {
            $properties       = $this->getProperties();
            $response         = $request->execute();
            $this->cronStatus = false;

            if ($response && method_exists($response, 'getRecords')) {
                $result = $response->getRecords();
                $lastEntryNo = $response->getLastEntryNo();
                $lastKey = $response->getLastKey();
                $remaining = $response->getEndOfTable() ? 0 : 1;
                $this->recordsRemaining = $remaining;

                if ($result != null) {
                    // @codingStandardsIgnoreLine
                    if (count($result) > 0) {
                        foreach ($result as $source) {
                            //TODO need to understand this before we modify it.
                            $source->setScope($this->defaultScope)
                                ->setScopeId($storeId);

                            $this->saveSource($properties, $source);
                        }
                        $this->updateSuccessStatus($storeId);
                    }
                }

                if ($remaining == 0) {
                    $this->cronStatus = true;
                }
                $this->persistLastKey($lastKey, $storeId);
                $this->persistLastEntryNo($lastEntryNo, $storeId);
                $this->rep_helper->updateCronStatus(
                    $this->cronStatus,
                    $this->getConfigPathStatus(),
                    $storeId,
                    false,
                    $this->defaultScope
                );
            } else {
                $this->logger->debug(
                    'No result found for ' .
                    get_class($this->getMainEntity()) .
                    '. Please refer omniclient log for details.'
                );
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Set default scope
     */
    public function setDefaultScope()
    {
        $lsr = $this->getLsrModel();

        if ($lsr->isSSM()) {
            $this->defaultScope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        } else {
            $confPath = $this->getConfigPath();
            if ($confPath == ReplLscDataTranslationTask::CONFIG_PATH ||
                $confPath == ReplLscItemHtmlMlTask::CONFIG_PATH
            ) {
                $this->defaultScope = ScopeInterface::SCOPE_STORES;
            }
        }
    }

    /**
     * Execute discount replication for central type saas or on-prem
     *
     * @param $lsr
     * @param $store
     * @param $scope
     * @return bool
     */
    public function executeDiscountReplicationOnCentralType($lsr, $store, $scope)
    {
        $configPath = $this->getConfigPath();

        if ($configPath == "ls_mag/replication/repl_discount_setup") {
            return !$lsr->validateForOlderVersion($store, $scope)['discountSetup'];
        }

        if ($configPath == "ls_mag/replication/repl_discount") {
            return !$lsr->validateForOlderVersion($store, $scope)['discount'];
        }

        return false;
    }

    /**
     * Get config path
     *
     * @return string
     */
    abstract public function getConfigPath();

    /**
     * Get status config path
     *
     * @return string
     */
    abstract public function getConfigPathStatus();

    /**
     * Get last_execute config path
     *
     * @return string
     */
    abstract public function getConfigPathLastExecute();

    /**
     * Get last entry no config path
     *
     * @return string
     */
    abstract public function getConfigPathLastEntryNo();

    /**
     * Making request with required parameters
     *
     * @param string $baseUrl
     * @param array $connectionParams
     * @param string $companyName
     * @param bool $fullRepl
     * @param int $batchSize
     * @param string $storeNo
     * @param int $lastEntryNo
     * @param string $lastKey
     * @return mixed
     */
    abstract public function makeRequest(
        string $baseUrl = '',
        array $connectionParams = [],
        string $companyName = '',
        bool $fullRepl = false,
        int $batchSize = 100,
        string $storeNo = '',
        int $lastEntryNo = 0,
        string $lastKey = ''
    );

    /**
     * Get factory instance
     *
     * @return mixed
     */
    abstract public function getFactory();

    /**
     * Get repository instance
     *
     * @return mixed
     */
    abstract public function getRepository();

    /**
     * Get main entry model class
     *
     * @return mixed
     */
    abstract public function getMainEntity();
}
