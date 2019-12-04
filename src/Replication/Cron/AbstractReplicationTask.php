<?php

namespace Ls\Replication\Cron;

use IteratorAggregate;
use \Ls\Core\Helper\Data as LsHelper;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\OperationInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Class AbstractReplicationTask
 * @package Ls\Replication\Cron
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
        'ls_mag/replication/repl_discount'
    ];

    /** @var array List of Replication Tables with unique field for delete */
    private static $deleteJobCodeUniqueFieldArray = [
        "ls_mag/replication/repl_item_variant_registration" => [
            "ItemId",
            "VariantDimension1",
            "VariantDimension2",
            "VariantDimension3",
            "VariantDimension4",
            "VariantDimension5",
            "VariantDimension6"
        ]
    ];

    /** @var array List of Replication Tables with unique field */
    private static $jobCodeUniqueFieldArray = [
        "ls_mag/replication/repl_attribute"                 => ["Code"],
        "ls_mag/replication/repl_attribute_option_value"    => ["Code", "Sequence", "Value"],
        "ls_mag/replication/repl_attribute_value"           => [
            "Code",
            "LinkField1",
            "LinkField2",
            "LinkField3",
            "Value"
        ],
        "ls_mag/replication/repl_barcode"                   => ["nav_id"],
        "ls_mag/replication/repl_country_code"              => ["Name"],
        "ls_mag/replication/repl_currency"                  => ["CurrencyCode"],
        "ls_mag/replication/repl_currency_exch_rate"        => ["CurrencyCode"],
        "ls_mag/replication/repl_customer"                  => ["AccountNumber"],
        "ls_mag/replication/repl_data_translation"          => ["TranslationId"],
        "ls_mag/replication/repl_discount"                  => [
            "ItemId",
            "LoyaltySchemeCode",
            "OfferNo",
            "StoreId",
            "VariantId",
            "MinimumQuantity"
        ],
        "ls_mag/replication/repl_discount_validation"       => ["nav_id"],
        "ls_mag/replication/repl_extended_variant_value"    => [
            "Code",
            "FrameworkCode",
            "ItemId",
            "Value"
        ],
        "ls_mag/replication/repl_hierarchy"                 => ["nav_id"],
        "ls_mag/replication/repl_hierarchy_leaf"            => ["nav_id", "NodeId"],
        "ls_mag/replication/repl_hierarchy_node"            => ["nav_id"],
        "ls_mag/replication/repl_image"                     => ["nav_id"],
        "ls_mag/replication/repl_image_link"                => ["ImageId", "KeyValue"],
        "ls_mag/replication/repl_item"                      => ["nav_id"],
        "ls_mag/replication/repl_item_category"             => ["nav_id"],
        "ls_mag/replication/repl_item_unit_of_measure"      => ["Code", "ItemId"],
        "ls_mag/replication/repl_item_variant_registration" => [
            "ItemId",
            "VariantId"
        ],
        "ls_mag/replication/repl_loy_vendor_item_mapping"   => ["NavManufacturerId", "NavProductId"],
        "ls_mag/replication/repl_price"                     => [
            "ItemId",
            "VariantId",
            "StoreId",
            "QtyPerUnitOfMeasure",
            "UnitOfMeasure"
        ],
        "ls_mag/replication/repl_inv_status"                => ["ItemId", "VariantId", "StoreId"],
        "ls_mag/replication/repl_product_group"             => ["nav_id"],
        "ls_mag/replication/repl_shipping_agent"            => ["Name"],
        "ls_mag/replication/repl_store"                     => ["nav_id"],
        "ls_mag/replication/repl_store_tender_type"         => ["StoreID", "TenderTypeId"],
        "ls_mag/replication/repl_unit_of_measure"           => ["nav_id"],
        "ls_mag/replication/repl_vendor"                    => ["Name"],
        "ls_mag/replication/loy_item"                       => ["nav_id"]
    ];

    /** @var LoggerInterface */
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

    /**
     * AbstractReplicationTask constructor.
     * @param ScopeConfigInterface $scope_config
     * @param Config $resouce_config
     * @param LoggerInterface $logger
     * @param LsHelper $helper
     * @param ReplicationHelper $repHelper
     */
    public function __construct(
        ScopeConfigInterface $scope_config,
        Config $resouce_config,
        LoggerInterface $logger,
        LsHelper $helper,
        ReplicationHelper $repHelper
    ) {
        $this->scope_config    = $scope_config;
        $this->resource_config = $resouce_config;
        $this->logger          = $logger;
        $this->ls_helper       = $helper;
        $this->rep_helper      = $repHelper;
    }

    /**
     * @throws \ReflectionException
     */
    public function execute()
    {
        $lsr = $this->getLsrModel();
        if ($lsr->isLSR()) {
            $this->rep_helper->updateConfigValue(date('d M,Y h:i:s A'), $this->getConfigPathLastExecute());
            $properties      = $this->getProperties();
            $last_key        = $this->getLastKey();
            $remaining       = INF;
            $fullReplication = 1;
            $isFirstTime     = $this->isFirstTime();
            if (isset($isFirstTime) && $isFirstTime == 1) {
                $fullReplication = 0;
                if ($this->isLastKeyAlwaysZero()) {
                    return;
                }
            }
            $batchSize      = 100;
            $isBatchSizeSet = $lsr->getStoreConfig(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE);
            if ($isBatchSizeSet and is_numeric($isBatchSizeSet)) {
                $batchSize = $isBatchSizeSet;
            }
            $isAllStoresItemsSet = $lsr->getStoreConfig(LSR::SC_REPLICATION_ALL_STORES_ITEMS);
            if ($isAllStoresItemsSet) {
                $webStoreID = '';
                if (in_array($this->getConfigPath(), self::$store_id_needed)) {
                    $webStoreID = $lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
                }
            } else {
                $webStoreID = $lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
            }
            $request                = $this->makeRequest($last_key, $fullReplication, $batchSize, $webStoreID);
            $response               = $request->execute();
            $result                 = $response->getResult();
            $last_key               = $result->getLastKey();
            $remaining              = $result->getRecordsRemaining();
            $this->recordsRemaining = $remaining;
            $traversable            = $this->getIterator($result);
            if ($traversable != null) {
                // @codingStandardsIgnoreStart
                if (count($traversable) > 0) {
                    // @codingStandardsIgnoreEnd
                    foreach ($traversable as $source) {
                        $this->saveSource($properties, $source);
                    }
                    $this->updateSuccessStatus();
                }
                $this->persistLastKey($last_key);
                if ($remaining == 0) {
                    $this->saveReplicationStatus(1);
                }
            }
            $this->rep_helper->flushConfig();
        } else {
            $this->logger->debug("LS Retail validation failed.");
        }
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function executeManually()
    {
        $this->execute();
        return [$this->recordsRemaining];
    }

    /**
     * Update the Custom Replication Success Status
     */
    public function updateSuccessStatus()
    {
        $confPath = $this->getConfigPath();
        if ($confPath == "ls_mag/replication/repl_attribute") {
            $this->rep_helper->updateCronStatus(false, LSR::SC_SUCCESS_CRON_ATTRIBUTE);
        } elseif ($confPath == "ls_mag/replication/repl_extended_variant_value") {
            $this->rep_helper->updateCronStatus(false, LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT);
        } elseif ($confPath == "ls_mag/replication/repl_hierarchy_node") {
            $this->rep_helper->updateCronStatus(false, LSR::SC_SUCCESS_CRON_CATEGORY);
        } elseif ($confPath == "ls_mag/replication/repl_discount") {
            $this->rep_helper->updateCronStatus(false, LSR::SC_SUCCESS_CRON_DISCOUNT);
        } elseif ($confPath == "ls_mag/replication/repl_item" ||
            $confPath == "ls_mag/replication/repl_hierarchy_leaf") {
            $this->rep_helper->updateCronStatus(false, LSR::SC_SUCCESS_CRON_PRODUCT);
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
            $uniqueAttributes = (array_key_exists($this->getConfigPath(),
                self::$deleteJobCodeUniqueFieldArray)) ? self::$deleteJobCodeUniqueFieldArray[$this->getConfigPath()] : self::$jobCodeUniqueFieldArray[$this->getConfigPath()];
        } else {
            $uniqueAttributes = self::$jobCodeUniqueFieldArray[$this->getConfigPath()];
        }
        $entityArray = $this->checkEntityExistByAttributes($uniqueAttributes, $source);
        if (!empty($entityArray)) {
            foreach ($entityArray as $value) {
                $entity = $value;
            }
            $entity->setIsUpdated(1);
            $entity->setIsFailed(0);
        } else {
            $entity = $this->getFactory()->create();
            $entity->setScope('default')->setScopeId(0);
        }
        foreach ($properties as $property) {
            if ($property === 'nav_id') {
                $set_method = 'setNavId';
                $get_method = 'getId';
            } else {
                $set_method = "set$property";
                $get_method = "get$property";
            }
            if (method_exists($entity, $set_method) && method_exists($source, $get_method)) {
                $entity->{$set_method}($source->{$get_method}());
            }
        }
        try {
            $this->getRepository()->save($entity);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @return string[]
     */
    final public function getProperties()
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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // @codingStandardsIgnoreStart
        $criteria = $objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
        // @codingStandardsIgnoreEnd
        foreach ($uniqueAttributes as $attribute) {
            if ($attribute == 'nav_id') {
                $get_method = 'getId';
            } else {
                $get_method = "get$attribute";
            }
            if ($notAnArraysObject) {
                foreach ($source as $keyprop => $valueprop) {
                    if ($get_method == 'get' . $keyprop) {
                        $sourceValue = $valueprop;
                        break;
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
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
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
     * @return string
     */
    public function getLastKey()
    {
        return $this->scope_config->getValue($this->getConfigPath(), ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * @return string
     */
    public function isFirstTime()
    {
        return $this->scope_config->getValue($this->getConfigPathStatus(), ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * @param string
     */
    public function persistLastKey($last_key)
    {
        $this->resource_config->saveConfig(
            $this->getConfigPath(),
            $last_key,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }

    /**
     * @param int $status
     */
    public function saveReplicationStatus($status = 0)
    {
        $this->resource_config->saveConfig(
            $this->getConfigPathStatus(),
            $status,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }

    /**
     * @param $result
     * @return null|\Traversable
     * @throws \ReflectionException
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
     * @return \Ls\Core\Model\LSR
     */
    public function getLsrModel()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // @codingStandardsIgnoreStart
        return $objectManager->get('\Ls\Core\Model\LSR');
        // @codingStandardsIgnoreEnd
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
     * @param $last_key
     *
     * @return OperationInterface
     */
    abstract public function makeRequest($last_key);

    abstract public function getFactory();

    abstract public function getRepository();

    abstract public function getMainEntity();
}
