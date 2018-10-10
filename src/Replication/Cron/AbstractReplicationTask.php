<?php

namespace Ls\Replication\Cron;

use IteratorAggregate;
use Ls\Core\Helper\Data as LsHelper;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Omni\Client\OperationInterface;
use Ls\Replication\Model\ReplHierarchyNode;
use Ls\Replication\Model\ReplHierarchyNodeRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Magento\Config\Model\ResourceModel\Config;
use Ls\Core\Model\LSR;

abstract class AbstractReplicationTask
{
    /** @var array */
    static private $bypass_methods = ['getMaxKey', 'getLastKey', 'getRecordsRemaining'];
    /** @var array All those config path don't have no lastkey means always zero as LastKey */
    static private $no_lastkey_config_path = [
        'ls_mag/replication/repl_country_code',
        'ls_mag/replication/repl_shipping_agent',
        'ls_mag/replication/repl_store_tender_type',
    ];
    /** @var array Config path which needed web store id instead of empty */
    static private $store_id_needed = [
        'ls_mag/replication/repl_hierarchy',
        'ls_mag/replication/repl_hierarchy_node',
        'ls_mag/replication/repl_hierarchy_leaf',
        'ls_mag/replication/repl_store_tender_type',
        'ls_mag/replication/repl_discount'
    ];

    /** @var array List of Replication Tables with unique field */
    static private $jobCodeUniqueFieldArray = array(
        "ls_mag/replication/repl_attribute" => array("Code"),
        "ls_mag/replication/repl_attribute_option_value" => array("Code", "Sequence"),
        "ls_mag/replication/repl_attribute_value" => array("Code", "LinkField1"),
        "ls_mag/replication/repl_barcode" => array("nav_id"),
        "ls_mag/replication/repl_country_code" => array("Name"),
        "ls_mag/replication/repl_currency" => array("CurrencyCode"),
        "ls_mag/replication/repl_currency_exch_rate" => array("CurrencyCode"),
        "ls_mag/replication/repl_customer" => array("AccountNumber"),
        "ls_mag/replication/repl_data_translation" => array("TranslationId"),
        "ls_mag/replication/repl_discount" => array("ItemId", "OfferNo","StoreId"),
        "ls_mag/replication/repl_discount_validation" => array("nav_id"),
        "ls_mag/replication/repl_extended_variant_value" => array("Code", "FrameworkCode", "ItemId"),
        "ls_mag/replication/repl_hierarchy" => array("nav_id"),
        "ls_mag/replication/repl_hierarchy_leaf" => array("nav_id"),
        "ls_mag/replication/repl_hierarchy_node" => array("nav_id"),
        "ls_mag/replication/repl_image" => array("nav_id"),
        "ls_mag/replication/repl_image_link" => array("ImageId", "KeyValue"),
        "ls_mag/replication/repl_item" => array("nav_id"),
        "ls_mag/replication/repl_item_category" => array("nav_id"),
        "ls_mag/replication/repl_item_unit_of_measure" => array("Code", "ItemId"),
        "ls_mag/replication/repl_item_variant_registration" => array("ItemId", "VariantId"),
        "ls_mag/replication/repl_loy_vendor_item_mapping" => array("NavManufacturerId", "NavProductId"),
        "ls_mag/replication/repl_price" => array("ItemId", "VariantId"),
        "ls_mag/replication/repl_product_group" => array("nav_id"),
        "ls_mag/replication/repl_shipping_agent" => array("Name"),
        "ls_mag/replication/repl_store" => array("nav_id"),
        "ls_mag/replication/repl_store_tender_type" => array("StoreID", "TenderTypeId"),
        "ls_mag/replication/repl_unit_of_measure" => array("nav_id"),
        "ls_mag/replication/repl_vendor" => array("Name")
    );

    /** @var LoggerInterface */
    protected $logger;
    /** @var ScopeConfigInterface */
    protected $scope_config;
    /** @var Config */
    protected $resource_config;
    /** @var LsHelper */
    protected $ls_helper;
    /** @var null */
    protected $iterator_method = null;
    /** @var null */
    protected $properties = null;

    /**
     * AbstractReplicationTask constructor.
     *
     * @param ScopeConfigInterface $scope_config
     * @param Config $resouce_config
     * @param LoggerInterface $logger
     * @param LsHelper $helper
     *
     * @internal param \Magento\Framework\ObjectManager\ContextInterface $context
     */
    public function __construct(
        ScopeConfigInterface $scope_config,
        Config $resouce_config,
        LoggerInterface $logger,
        LsHelper $helper
    )
    {
        $this->scope_config = $scope_config;
        $this->resource_config = $resouce_config;
        $this->logger = $logger;
        $this->ls_helper = $helper;
    }

    /**
     * @throws \ReflectionException
     */
    function execute()
    {
        $lsr = $this->getLsrModel();
        if ($lsr->isLSR()) {
            $properties = $this->getProperties();
            $last_key = $this->getLastKey();
            $remaining = INF;
            $fullReplication = 1;
            $isFirstTime = $this->isFirstTime();
            if (isset($isFirstTime) && $isFirstTime == 1) {
                $fullReplication = 0;
                if ($this->isLastKeyAlwaysZero())
                    return;
            }
            $batchSize = 100;
            $isBatchSizeSet = $lsr->getStoreConfig(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE);
            if ($isBatchSizeSet and is_numeric($isBatchSizeSet)) {
                $batchSize = $isBatchSizeSet;
            }
            $webStoreID = '';
            if (in_array($this->getConfigPath(), self::$store_id_needed)) {
                $webStoreID = $lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
            }
            while ($remaining != 0) {
                $request = $this->makeRequest(0, $fullReplication, $batchSize, $webStoreID);
                $response = $request->execute();
                $result = $response->getResult();
                $last_key = $result->getLastKey();
                $remaining = $result->getRecordsRemaining();
                $traversable = $this->getIterator($result);
                if (!is_null($traversable)) {
                    if (count($traversable) > 0) {
                        foreach ($traversable as $source) {
                            $this->saveSource($properties, $source);
                        }
                    } else {
                        $arrayTraversable = (array)$traversable;
                        if (count($arrayTraversable) > 0) {
                            $entityClass = new ReflectionClass($this->getMainEntity());
                            $singleObject = (object)$traversable->getArrayCopy();
                            $entity = $this->getFactory()->create();
                            $entity->setScope('default')->setScopeId(0);
                            foreach ($singleObject as $keyprop=>$valueprop) {
                                if ($keyprop == 'nav_id') {
                                    $set_method = 'setNavId';
                                } else {
                                    $set_method = "set$keyprop";
                                }
                                $entity->{$set_method}($valueprop);
                            }
                            try {
                                $this->getRepository()->save($entity);
                            } catch (\Exception $e) {
                                $this->logger->debug($e->getMessage());
                            }
                        }
                    }
                }
                $this->persistLastKey($last_key);
                $this->saveReplicationStatus(1);
            }
        } else {
            $this->logger->debug("LS Retail validation failed.");
        }
    }

    protected function toObject(array $array, $object)
    {
        $class = get_class($object);
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            preg_match(' /^(set)(.*?)$/i', $method, $results);
            $pre = $results[1] ?? '';
            $k = $results[2] ?? '';
            $k = strtolower(substr($k, 0, 1)) . substr($k, 1);
            if ($pre == 'set' && !empty($array[$k])) {
                $object->$method($array[$k]);
            }
        }
        return $object;
    }


    protected function saveSource($properties, $source)
    {
        $uniqueAttributes = self::$jobCodeUniqueFieldArray[$this->getConfigPath()];
        if ($this->checkEntityExistByAttributes($uniqueAttributes, $source)) {
            $entityArray = $this->checkEntityExistByAttributes($uniqueAttributes, $source);
            foreach ($entityArray as $value) {
                $entity = $value;
            }
            $entity->setIsUpdated(1);
        } else {
            $entity = $this->getFactory()->create();
            $entity->setScope('default')->setScopeId(0);
        }
        foreach ($properties as $property) {
            if ($property == 'nav_id') {
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
    protected final function getProperties()
    {
        if (is_null($this->properties)) {
            $reflected_entity = new ReflectionClass($this->getMainEntity());

            $properties = [];
            foreach ($reflected_entity->getProperties() as $property) {
                $properties[] = $property->getName();
            }
            $this->properties = $properties;
        }

        return $this->properties;
    }

    /**
     * @param $uniqueAttributes
     * @param $source
     * @return bool
     */
    protected function checkEntityExistByAttributes($uniqueAttributes, $source)
    {
        // TODO create SearchCriteria Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $criteria = $objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
        try {
            foreach ($uniqueAttributes as $attribute) {
                if ($attribute == 'nav_id') {
                    $get_method = 'getId';
                } else {
                    $get_method = "get$attribute";
                }
                $criteria->addFilter($attribute, $source->{$get_method}());
            }
            $result = $this->getRepository()->getList($criteria->create());
            return $result->getItems();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param $nav_id
     * @return bool
     */
    protected function checkNavIdExist($nav_id)
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
    protected function isLastKeyAlwaysZero()
    {
        if (in_array($this->getConfigPath(), self::$no_lastkey_config_path)) {
            return true;
        } else
            return false;
    }

    /**
     * @return string
     */
    protected function getLastKey()
    {
        return $this->scope_config->getValue($this->getConfigPath(), ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * @return string
     */
    protected function isFirstTime()
    {
        return $this->scope_config->getValue($this->getConfigPathStatus(), ScopeConfigInterface::SCOPE_TYPE_DEFAULT);

    }

    /**
     * @param  string
     */
    protected function persistLastKey($last_key)
    {
        $this->resource_config->saveConfig($this->getConfigPath(), $last_key,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
    }

    /**
     * @param int $status
     */
    protected function saveReplicationStatus($status = 0)
    {
        $this->resource_config->saveConfig($this->getConfigPathStatus(), $status,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
    }

    /**
     * @param $result
     * @return null|\Traversable
     * @throws \ReflectionException
     */
    protected function getIterator($result)
    {
        if (is_null($this->iterator_method)) {
            $reflected = new ReflectionClass($result);
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
     * We cant use the DI method to get LSR model in here, so we need to use the object manager approach to get LSR model.
     * @return \Ls\Core\Model\LSR
     */
    protected function getLsrModel()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->get('\Ls\Core\Model\LSR');
    }

    /**
     * @return string
     */
    abstract function getConfigPath();

    /**
     * @return string
     */
    abstract function getConfigPathStatus();

    /**
     * @param $last_key
     *
     * @return OperationInterface
     */
    abstract function makeRequest($last_key);

    abstract function getFactory();

    abstract function getRepository();

    abstract function getMainEntity();
}

