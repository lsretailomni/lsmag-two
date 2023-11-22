<?php

namespace Ls\Replication\Cron;

use Exception;
use IteratorAggregate;
use \Ls\Core\Model\Data as LsHelper;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\OperationInterface;
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
use ReflectionClass;
use ReflectionException;
use Traversable;

/**
 * Abstract replication class for all
 * the flat tables
 */
abstract class AbstractReplicationTask
{
    /** @var array */
    private static $bypass_methods = ['getMaxKey', 'getLastKey', 'getRecordsRemaining'];

    /** @var array All those config path don't have no lastkey means always zero as LastKey */
    private static $no_lastkey_config_path = [
        'ls_mag/replication/repl_country_code',
        'ls_mag/replication/repl_shipping_agent',
        'ls_mag/replication/repl_store_tender_type',
        'ls_mag/replication/repl_inv_status'
    ];

    /** @var array Config path which needed web store id instead of empty */
    private static $store_id_needed = [
        'ls_mag/replication/repl_hierarchy',
        'ls_mag/replication/repl_hierarchy_node',
        'ls_mag/replication/repl_hierarchy_leaf',
        'ls_mag/replication/repl_store_tender_type',
        'ls_mag/replication/repl_discount',
        'ls_mag/replication/repl_discount_setup',
    ];

    /** @var array List of Replication Tables with unique field for delete */
    public static $deleteJobCodeUniqueFieldArray = [
        "ls_mag/replication/repl_item_variant_registration" => [
            "ItemId",
            "VariantDimension1",
            "VariantDimension2",
            "VariantDimension3",
            "VariantDimension4",
            "VariantDimension5",
            "VariantDimension6"
        ],
        "ls_mag/replication/repl_hierarchy_hosp_deal_line"  => ["DealNo", "DealLineNo", "LineNo", "scope_id"],

    ];

    /** @var array List of Replication Tables with unique field */
    public static $jobCodeUniqueFieldArray = [
        "ls_mag/replication/repl_attribute"                  => ["Code", "scope_id"],
        "ls_mag/replication/repl_attribute_option_value"     => ["Code", "Sequence", "scope_id"],
        "ls_mag/replication/repl_attribute_value"            => [
            "Code",
            "LinkField1",
            "LinkField2",
            "LinkField3",
            "Sequence",
            "scope_id"
        ],
        "ls_mag/replication/repl_barcode"                    => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_country_code"               => ["Name", "scope_id"],
        "ls_mag/replication/repl_currency"                   => ["CurrencyCode", "scope_id"],
        "ls_mag/replication/repl_currency_exch_rate"         => ["CurrencyCode", "scope_id"],
        "ls_mag/replication/repl_customer"                   => ["AccountNumber", "scope_id"],
        "ls_mag/replication/repl_data_translation"           => ["TranslationId", "Key", "LanguageCode", "scope_id"],
        "ls_mag/replication/repl_html_translation"           => ["TranslationId", "Key", "LanguageCode", "scope_id"],
        "ls_mag/replication/repl_deal_html_translation"      => ["TranslationId", "Key", "LanguageCode", "scope_id"],
        "ls_mag/replication/repl_data_translation_lang_code" => ["Code", "scope_id"],
        "ls_mag/replication/repl_discount"                   => [
            "ItemId",
            "LoyaltySchemeCode",
            "OfferNo",
            "StoreId",
            "VariantId",
            "MinimumQuantity",
            "scope_id"
        ],
        "ls_mag/replication/repl_discount_setup"             => [
            "Number",
            "LoyaltySchemeCode",
            "OfferNo",
            "Type",
            "Enabled",
            "VariantId",
            "scope_id"
        ],
        "ls_mag/replication/repl_discount_validation"        => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_extended_variant_value"     => [
            "Code",
            "FrameworkCode",
            "ItemId",
            "Value",
            "scope_id"
        ],
        "ls_mag/replication/repl_hierarchy"                  => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_hierarchy_leaf"             => ["nav_id", "NodeId", "scope_id"],
        "ls_mag/replication/repl_hierarchy_node"             => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_image"                      => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_image_link"                 => ["ImageId", "KeyValue", "scope_id"],
        "ls_mag/replication/repl_item"                       => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_item_category"              => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_item_unit_of_measure"       => ["Code", "ItemId", "scope_id"],
        "ls_mag/replication/repl_item_variant_registration"  => [
            "ItemId",
            "VariantId",
            "scope_id"
        ],
        "ls_mag/replication/repl_item_variant"               => [
            "ItemId",
            "VariantId",
            "scope_id"
        ],
        "ls_mag/replication/repl_loy_vendor_item_mapping"    => ["NavManufacturerId", "NavProductId", "scope_id"],
        "ls_mag/replication/repl_price"                      => [
            "ItemId",
            "VariantId",
            "StoreId",
            "QtyPerUnitOfMeasure",
            "UnitOfMeasure",
            "scope_id"
        ],
        "ls_mag/replication/repl_inv_status"                 => ["ItemId", "VariantId", "StoreId", "scope_id"],
        "ls_mag/replication/repl_product_group"              => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_shipping_agent"             => ["Name", "scope_id"],
        "ls_mag/replication/repl_store"                      => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_store_tender_type"          => ["TenderTypeId", "scope_id"],
        "ls_mag/replication/repl_unit_of_measure"            => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_vendor"                     => ["Name", "scope_id"],
        "ls_mag/replication/repl_hierarchy_hosp_deal_line"   => [
            "DealNo",
            "ItemNo",
            "LineNo",
            "UnitOfMeasure",
            "scope_id"
        ],
        "ls_mag/replication/repl_hierarchy_hosp_deal"        => ["DealNo", "No", "LineNo", "UnitOfMeasure", "scope_id"],
        "ls_mag/replication/repl_item_recipe"                => ["ItemNo", "RecipeNo", "UnitOfMeasure", "scope_id"],
        "ls_mag/replication/repl_item_modifier"              => [
            "nav_id",
            "VariantCode",
            "Code", "SubCode",
            "TriggerCode",
            "UnitOfMeasure",
            "scope_id"
        ],
        "ls_mag/replication/loy_item"                        => ["nav_id", "scope_id"],
        "ls_mag/replication/repl_tax_setup"                  => ["BusinessTaxGroup", "ProductTaxGroup", "scope_id"]
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
    public $iterator_method = null;
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
     * AbstractReplicationTask constructor.
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
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
        if ($this->defaultScope == ScopeInterface::SCOPE_WEBSITES) {
            /**
             * Get all the available stores config in the Magento system
             */
            $lsr = $this->getLsrModel();
            if (!empty($storeData) && $storeData instanceof WebsiteInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->getAllWebsites();
            }
            if (!empty($stores)) {
                foreach ($stores as $store) {
                    if ($this->getLsrModel()->isEnabled($store->getId(), $this->defaultScope)) {

                        if ($this->executeDiscountReplicationOnCentralType($lsr, $store)) {
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
     * @param null $storeData
     * @return array
     * @throws ReflectionException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        return [$this->recordsRemaining];
    }

    /**
     * Update the Custom Replication Success Status
     * @param bool $storeId
     */
    public function updateSuccessStatus($storeId = false)
    {
        $confPath = $this->getConfigPath();
        if ($confPath == "ls_mag/replication/repl_attribute" ||
            $confPath == "ls_mag/replication/repl_attribute_option_value") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ATTRIBUTE);
        } elseif ($confPath == "ls_mag/replication/repl_extended_variant_value") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT);
        } elseif ($confPath == "ls_mag/replication/repl_item_variant") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT);
        } elseif ($confPath == "ls_mag/replication/repl_hierarchy_node") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_CATEGORY);
        } elseif ($confPath == "ls_mag/replication/repl_discount") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_DISCOUNT);
        } elseif ($confPath == "ls_mag/replication/repl_discount_setup") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP);
        } elseif ($confPath == "ls_mag/replication/repl_discount_validation") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_DISCOUNT_VALIDATION);
        } elseif ($confPath == "ls_mag/replication/repl_item") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_PRODUCT);
        } elseif ($confPath == "ls_mag/replication/repl_hierarchy_leaf") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ITEM_UPDATES);
        } elseif ($confPath == "ls_mag/replication/repl_vendor") {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_VENDOR);
        } elseif ($confPath == "ls_mag/replication/repl_loy_vendor_item_mapping") {
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
     * @param array $array
     * @param $object
     * @return mixed
     */
    public function toObject(array $array, $object)
    {
        $class   = get_class($object);
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            preg_match(' /^(set)(.*?)$/i', $method, $results);
            $pre = $results[1] ?? '';
            $k   = $results[2] ?? '';
            $k   = strtolower(substr($k, 0, 1)) . substr($k, 1);
            if ($pre == 'set' && !empty($array[$k])) {
                $object->$method($array[$k]);
            }
        }
        return $object;
    }

    /**
     * @param $properties
     * @param $source
     */
    public function saveSource($properties, $source)
    {
        if ($source->getIsDeleted()) {
            $uniqueAttributes = (array_key_exists($this->getConfigPath(), self::$deleteJobCodeUniqueFieldArray)) ?
                self::$deleteJobCodeUniqueFieldArray[$this->getConfigPath()] :
                self::$jobCodeUniqueFieldArray[$this->getConfigPath()];
        } else {
            $uniqueAttributes = self::$jobCodeUniqueFieldArray[$this->getConfigPath()];
        }
        $confPath = $this->getConfigPath();
        if ($confPath == "ls_mag/replication/repl_discount_validation") {
            $source->setStartDate($this->rep_helper->convertDateTimeIntoCurrentTimeZone(
                $source->getStartDate(),
                LSR::DATE_FORMAT
            ));

            $source->setStartTime($this->rep_helper->convertDateTimeIntoCurrentTimeZone(
                $source->getStartTime(),
                LSR::TIME_FORMAT
            ));
            $source->setEndDate($this->rep_helper->convertDateTimeIntoCurrentTimeZone(
                $source->getEndDate(),
                LSR::DATE_FORMAT
            ));

            $source->setEndTime($this->rep_helper->convertDateTimeIntoCurrentTimeZone(
                $source->getEndTime(),
                LSR::TIME_FORMAT
            ));
        }
        // phpcs:ignore Magento2.Security.InsecureFunction
        $checksum    = crc32(serialize($source));
        $entityArray = $this->checkEntityExistByAttributes($uniqueAttributes, $source);
        if (!empty($entityArray)) {
            foreach ($entityArray as $value) {
                $entity = $value;
            }
            $entity->setIsUpdated(1);
            $entity->setIsFailed(0);
            $entity->setUpdatedAt($this->rep_helper->getDateTime());
        } else {
            $entity = $this->getFactory()->create();
        }
        if ($entity->getChecksum() != $checksum) {
            $entity->setChecksum($checksum);
            foreach ($properties as $property) {
                if ($property === 'nav_id') {
                    $set_method = 'setNavId';
                    $get_method = 'getId';
                } else {
                    $field_name_optimized   = str_replace('_', ' ', $property);
                    $field_name_capitalized = ucwords($field_name_optimized);
                    $field_name_capitalized = str_replace(' ', '', $field_name_capitalized);
                    $set_method             = "set$field_name_capitalized";
                    $get_method             = "get$field_name_capitalized";
                }
                if ($entity && $source && method_exists($entity, $set_method) && method_exists($source, $get_method)) {
                    $entity->{$set_method}($source->{$get_method}());
                }
            }
            try {
                $this->getRepository()->save($entity);
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
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
            $reflected_entity = new ReflectionClass($this->getMainEntity());
            // @codingStandardsIgnoreEnd
            $properties = [];
            foreach ($reflected_entity->getProperties() as $property) {
                $properties[] = $property->getName();
            }
            $this->properties = $properties;
        }
        return $this->properties;
    }

    /**
     * Check the Entity exist or not
     * @param $uniqueAttributes
     * @param $source
     * @param $notAnArraysObject
     * @return bool | array
     */
    public function checkEntityExistByAttributes($uniqueAttributes, $source, $notAnArraysObject = false)
    {
        $objectManager = $this->getObjectManager();
        // @codingStandardsIgnoreStart
        $criteria = $objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
        // @codingStandardsIgnoreEnd
        foreach ($uniqueAttributes as $attribute) {
            $field_name_optimized   = str_replace('_', ' ', $attribute);
            $field_name_capitalized = ucwords($field_name_optimized);
            $field_name_capitalized = str_replace(' ', '', $field_name_capitalized);

            if ($attribute == 'nav_id') {
                $get_method = 'getId';
            } else {
                $get_method = "get$field_name_capitalized";
            }

            if ($notAnArraysObject) {
                foreach ($source as $keyprop => $valueprop) {
                    if ($get_method == 'get' . $keyprop) {
                        $sourceValue = $valueprop;
                        if ($sourceValue != '') {
                            break;
                        }
                    }
                }
            } else {
                $sourceValue = $source->{$get_method}();
            }

            if ($sourceValue == "") {
                $criteria->addFilter($attribute, true, 'null');
            } else {
                $criteria->addFilter($attribute, $sourceValue);
            }
        }
        $result = $this->getRepository()->getList($criteria->create());
        return $result->getItems();
    }

    /**
     * @param $nav_id
     * @return bool
     */
    public function checkNavIdExist($nav_id)
    {
        try {
            $item = $this->getFactory()->create();
            return $item->loadByAttribute('nav_id', $nav_id);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Check LastKey is always zero or not using Replication Config Path
     * @return bool
     */
    public function isLastKeyAlwaysZero()
    {
        if (in_array($this->getConfigPath(), self::$no_lastkey_config_path)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param bool $storeId
     * @return string
     */
    public function getLastKey($storeId = false)
    {
        $lsrModel = $this->getLsrModel();
        if ($storeId) {
            return $lsrModel->getConfigValueFromDb(
                $this->getConfigPath(),
                $this->defaultScope,
                $storeId
            );
        } else {
            return $lsrModel->getConfigValueFromDb(
                $this->getConfigPath(),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
    }

    /**
     * @param bool $storeId
     * @return string
     */
    public function getMaxKey($storeId = false)
    {
        $lsrModel = $this->getLsrModel();

        if ($storeId) {
            return $lsrModel->getConfigValueFromDb(
                $this->getConfigPathMaxKey(),
                $this->defaultScope,
                $storeId
            );
        } else {
            return $lsrModel->getConfigValueFromDb(
                $this->getConfigPathMaxKey(),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
    }

    /**
     * @param bool $storeId
     * @return string
     */
    public function isFirstTime($storeId = false)
    {
        $lsrModel = $this->getLsrModel();
        if ($storeId) {
            return $lsrModel->getConfigValueFromDb(
                $this->getConfigPathStatus(),
                $this->defaultScope,
                $storeId
            );
        } else {
            return $lsrModel->getConfigValueFromDb(
                $this->getConfigPathStatus(),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
    }

    /**
     * @param string $lastKey
     * @param bool $storeId
     */
    public function persistLastKey($lastKey, $storeId = false)
    {
        if ($storeId) {
            $this->resource_config->saveConfig(
                $this->getConfigPath(),
                $lastKey,
                $this->defaultScope,
                $storeId
            );
        } else {
            $this->resource_config->saveConfig(
                $this->getConfigPath(),
                $lastKey,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
    }

    /**
     * @param string $maxKey
     * @param bool $storeId
     */
    public function persistMaxKey($maxKey, $storeId = false)
    {
        if ($storeId) {
            $this->resource_config->saveConfig(
                $this->getConfigPathMaxKey(),
                $maxKey,
                $this->defaultScope,
                $storeId
            );
        } else {
            $this->resource_config->saveConfig(
                $this->getConfigPathMaxKey(),
                $maxKey,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
    }

    /**
     * @param int $status
     */
    public function saveReplicationStatus($status = 0, $storeId = false)
    {
        if ($storeId) {
            $this->resource_config->saveConfig(
                $this->getConfigPathStatus(),
                $status,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
        } else {
            $this->resource_config->saveConfig(
                $this->getConfigPathStatus(),
                $status,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
    }

    /**
     * @param $result
     * @return null|Traversable
     * @throws ReflectionException
     */
    public function getIterator($result)
    {
        if ($this->iterator_method === null) {
            // @codingStandardsIgnoreStart
            $reflected = new ReflectionClass($result);
            // @codingStandardsIgnoreEnd
            foreach ($reflected->getMethods() as $method) {
                $method_name = $method->getName();
                if (strpos($method_name, 'get') === 0 && !in_array($method, self::$bypass_methods)) {
                    $this->iterator_method = $method_name;
                    break;
                }
            }
        }
        $iterable = $result->{$this->iterator_method}();

        if ($iterable instanceof IteratorAggregate) {
            return $iterable->getIterator();
        }
        return null;
    }

    /**
     * We cant use the DI method to get LSR model in here,
     * so we need to use the object manager approach to get LSR model.
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
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return ObjectManager::getInstance();
    }

    /**
     * @return StoreInterface[]
     */
    public function getAllStores()
    {
        return $this->getObjectManager()->get(StoreManagerInterface::class)->getStores();
    }

    /**
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
     * @param $lsr
     * @param $storeId
     * @return array
     */
    public function getRequiredParamsForMakingRequest($lsr, $storeId)
    {
        $lastKey    = $this->getLastKey($storeId);
        $maxKey     = $this->getMaxKey($storeId);
        $batchSize  = $this->getBatchSize($lsr, $storeId);
        $webStoreID = $this->getWebStoreId($lsr, $storeId);
        $baseUrl    = $this->getBaseUrl($lsr, $storeId);

        return [$lastKey, 1, $batchSize, $webStoreID, $maxKey, $baseUrl, ''];
    }

    /**
     * Make request Fetch Data for given store
     *
     * @param $storeId
     * @throws NoSuchEntityException
     */
    public function fetchDataGivenStore($storeId)
    {
        $lsr = $this->getLsrModel();
        // Need to check if is_lsr is enabled on each store and only process the relevant store.
        if ($lsr->isLSR($storeId, $this->defaultScope)) {
            $this->rep_helper->updateConfigValue(
                $this->rep_helper->getDateTime(),
                $this->getConfigPathLastExecute(),
                $storeId,
                $this->defaultScope
            );

            list($lastKey, $fullReplication, $batchSize, $webStoreID, $maxKey, $baseUrl, $appId) =
                $this->getRequiredParamsForMakingRequest($lsr, $storeId);

            $isFirstTime = $this->isFirstTime($storeId);

            if (isset($isFirstTime) && $isFirstTime == 1) {
                $fullReplication = 0;

                if ($this->isLastKeyAlwaysZero()) {
                    return;
                }
            }

            $request = $this->makeRequest(
                $lastKey,
                $fullReplication,
                $batchSize,
                $webStoreID,
                $maxKey,
                $baseUrl,
                $appId
            );

            $this->processResponseGivenRequest($request, $storeId, $isFirstTime);
        } else {
            $this->logger->debug('LS Retail validation failed for store id ' . $storeId);
        }
    }

    /**
     * Use given request and save response
     *
     * @param $request
     * @param $storeId
     * @param $isFirstTime
     */
    public function processResponseGivenRequest($request, $storeId, $isFirstTime = 1)
    {
        try {
            $properties = $this->getProperties();
            $response   = $request->execute();

            if ($response && method_exists($response, 'getResult')) {
                $result                 = $response->getResult();
                $lastKey                = $result->getLastKey();
                $maxKey                 = $result->getMaxKey();
                $remaining              = $result->getRecordsRemaining();
                $this->recordsRemaining = $remaining;
                $traversable            = $this->getIterator($result);

                if ($traversable != null) {
                    // @codingStandardsIgnoreLine
                    if (count($traversable) > 0) {
                        foreach ($traversable as $source) {
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
                $this->persistMaxKey($maxKey, $storeId);
                if (!isset($isFirstTime) || $isFirstTime == 0) {
                    $this->rep_helper->updateCronStatus(
                        $this->cronStatus,
                        $this->getConfigPathStatus(),
                        $storeId,
                        false,
                        $this->defaultScope
                    );
                }
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
     *
     */
    public function setDefaultScope()
    {
        $confPath = $this->getConfigPath();

        if ($confPath == ReplEcommDataTranslationTask::CONFIG_PATH ||
            $confPath == ReplEcommDataTranslationLangCodeTask::CONFIG_PATH ||
            $confPath == ReplEcommHtmlTranslationTask::CONFIG_PATH ||
            $confPath == ReplEcommDealHtmlTranslationTask::CONFIG_PATH
        ) {
            $this->defaultScope = ScopeInterface::SCOPE_STORES;
        }
    }

    /**
     * Execute discount replication for central type saas or on-prem
     *
     */
    public function executeDiscountReplicationOnCentralType($lsr, $store)
    {
        $configPath = $this->getConfigPath();

        if ($configPath == "ls_mag/replication/repl_discount_setup") {
            return !in_array('discountSetup', $lsr->validateForOlderVersion($store));
        }

        if ($configPath == "ls_mag/replication/repl_discount") {
            return !in_array('discount', $lsr->validateForOlderVersion($store));
        }

        return false;

    }

    /**
     * @return string
     */
    abstract public function getConfigPath();

    /**
     * @return string
     */
    abstract public function getConfigPathStatus();

    /**
     * @return string
     */
    abstract public function getConfigPathLastExecute();

    /**
     * @return string
     */
    abstract public function getConfigPathMaxKey();

    /**
     * @return string
     */
    abstract public function getConfigPathAppId();

    /**
     * Making request with required parameters
     *
     * @param $lastKey
     * @param $fullReplication
     * @param $batchSize
     * @param $storeId
     * @param $maxKey
     * @param $baseUrl
     * @param $appId
     * @return OperationInterface
     */
    abstract public function makeRequest($lastKey, $fullReplication, $batchSize, $storeId, $maxKey, $baseUrl, $appId);

    abstract public function getFactory();

    abstract public function getRepository();

    abstract public function getMainEntity();
}
