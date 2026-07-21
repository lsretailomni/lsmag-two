<?php
declare(strict_types=1);

namespace Ls\Replication\Cron;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\Data as LsHelper;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\LSCValidationPeriod;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountValueType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\HierarchyDealType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\HierarchyLeafType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\HierarchyType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ItemModifierPriceHandling;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ItemModifierPriceType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ItemModifierType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ItemTriggerFunction;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ItemUsageCategory;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscMemberType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountLineType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountType;
use \Ls\Omni\Client\CentralEcommerce\Entity\HierarchyDealView;
use \Ls\Omni\Client\CentralEcommerce\Entity\HierarchyView;
use \Ls\Omni\Client\CentralEcommerce\Entity\LSCDataTranslation;
use \Ls\Omni\Client\CentralEcommerce\Entity\LSCItemHTMLML;
use \Ls\Omni\Client\CentralEcommerce\Entity\LSCWIItemBuffer;
use \Ls\Omni\Client\CentralEcommerce\Entity\LSCWIItemModifier;
use \Ls\Omni\Client\CentralEcommerce\Entity\PeriodicDiscView;
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

/**
 * Abstract replication class for all
 * the flat tables
 */
abstract class AbstractReplicationTask
{
    /**
     * Default number of buffered rows flushed per INSERT ... ON DUPLICATE KEY UPDATE statement.
     */
    public const BATCH_CHUNK_SIZE = 1000;

    /**
     * Soft ceiling on bound placeholders per statement. MySQL's hard limit is 65,535; staying
     * well under it keeps wide flat tables safe.
     */
    private const PLACEHOLDER_BUDGET = 60000;

    /**
     * Pending batch rows keyed by config path. Each entry holds the column=>value map plus the
     * originating $source and $properties so a failed chunk can be replayed through the ORM path.
     *
     * @var array
     */
    protected $upsertBuffer = [];

    /**
     * Resolved flat-table name keyed by config path.
     *
     * @var array
     */
    protected $upsertTableCache = [];

    /**
     * Cached table description (column metadata) keyed by table name.
     *
     * @var array
     */
    protected $upsertTableDescriptions = [];

    /**
     * Whether a flat table has a single-column UNIQUE index on identity_value, keyed by table name.
     *
     * @var array
     */
    protected $identityUniqueIndexCache = [];

    /**
     * Count of rows in the current run that could not be written by either the batch path or the
     * ORM replay fallback. A non-zero value de-asserts the cron success status.
     *
     * @var int
     */
    protected $flushFailures = 0;

    /**
     * Aggregated fallback-to-ORM counts for the current run, keyed by reason.
     *
     * @var array
     */
    protected $fallbackCounts = [];

    /** @var array All those config path don't have no lastkey means always zero as LastKey */
    private static $no_lastkey_config_path = [
        'ls_mag/replication/repl_country_code',
        'ls_mag/replication/repl_shipping_agent',
        'ls_mag/replication/repl_store_tender_type',
        'ls_mag/replication/repl_inv_status'
    ];

    /** @var null */
    public $properties = null;
    /** @var integer */
    public $recordsRemaining = 0;
    /** @var bool */
    public $cronStatus = false;

    public $defaultScope = ScopeInterface::SCOPE_WEBSITES;

    /**
     * @param ScopeConfigInterface $scope_config
     * @param Config $resource_config
     * @param Logger $logger
     * @param LsHelper $ls_helper
     * @param ReplicationHelper $rep_helper
     */
    public function __construct(
        public ScopeConfigInterface $scope_config,
        public Config               $resource_config,
        public Logger               $logger,
        public LsHelper             $ls_helper,
        public ReplicationHelper    $rep_helper
    ) {
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
     * @param mixed $storeData
     * @return int[]
     * @throws NoSuchEntityException|GuzzleException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        return [$this->recordsRemaining];
    }

    /**
     * Update the Custom Replication Success Status
     *
     * @param string $storeId
     */
    public function updateSuccessStatus($storeId)
    {
        $confPath = $this->getConfigPath();

        if ($confPath == ReplLscAttributeTask::CONFIG_PATH ||
            $confPath == ReplLscAttributeOptionValueTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ATTRIBUTE);
        } elseif ($confPath == ReplLscWiExtdVariantValuesTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT);
        } elseif ($confPath == ReplLscItemVariantTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT);
        } elseif ($confPath == ReplLscHierarchynodesviewTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_CATEGORY);
        } elseif ($confPath == ReplLscPeriodicdiscviewTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP);
        } elseif ($confPath == ReplLscValidationPeriodTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_DISCOUNT_VALIDATION);
        } elseif ($confPath == ReplLscWiItemBufferTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_PRODUCT);
        } elseif ($confPath == ReplLscHierarchynodeslinkviewTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_ITEM_UPDATES);
        } elseif ($confPath == ReplLscVendorTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_VENDOR);
        } elseif ($confPath == ReplLscVendoritemviewTask::CONFIG_PATH) {
            $this->updateAllStoresConfigs($storeId, LSR::SC_SUCCESS_CRON_VENDOR_ATTRIBUTE);
        }
    }

    /**
     * Update all dependent flat to magento crons status
     *
     * @param string $websiteId
     * @param string $path
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
     * Save new source or update already existing source.
     *
     * Dispatcher: config-specific source formatting is applied once, then eligible rows are
     * buffered for a chunked INSERT ... ON DUPLICATE KEY UPDATE ({@see flushBuffer()}); everything
     * else falls back to the unchanged ORM path ({@see saveSourceOrm()}).
     *
     * @param array $properties
     * @param mixed $source
     * @throws Exception
     */
    public function saveSource($properties, $source)
    {
        $confPath = $this->getConfigPath();

        // Apply config-specific source formatting exactly once, before the batch/ORM split, so a
        // batched row and a possible ORM replay of it never double-apply the transform.
        $this->formatSourceColumns($source, $confPath);

        if (!$this->isBatchEligible($source, $confPath, $source->getScopeId())) {
            $this->saveSourceOrm($properties, $source);
            return;
        }

        try {
            $uniqueAttributes     = ReplicationHelper::JOB_CODE_UNIQUE_FIELD_ARRAY[$confPath];
            $checksum             = $this->getHashGivenString($source->getData());
            $uniqueAttributesHash = $this->generateIdentityValue($uniqueAttributes, $source, $properties);
            $row                  = $this->buildUpsertRow($properties, $source, $checksum, $uniqueAttributesHash);
        } catch (Exception $e) {
            $this->countFallback('build_error');
            $this->logger->debug('Batch upsert build error, falling back to ORM: ' . $e->getMessage());
            $this->saveSourceOrm($properties, $source);
            return;
        }

        $this->upsertBuffer[$confPath][] = [
            'row'        => $row,
            'source'     => $source,
            'properties' => $properties,
        ];

        $chunkLimit = min(self::BATCH_CHUNK_SIZE, $this->maxRowsPerChunk(count($row)));
        if (count($this->upsertBuffer[$confPath]) >= $chunkLimit) {
            $this->flushBuffer($confPath, $source->getScopeId());
        }
    }

    /**
     * Apply config-specific in-place column formatting to a source row before it is persisted.
     *
     * This is the config-specific transform chain lifted out of the original saveSource() body,
     * applied once by {@see saveSource()} before the batch/ORM split so the transform runs exactly
     * once even when a buffered row is later replayed through the ORM path on a flush failure. The
     * delete-time side-effect branches (base price ItemId reset, retail image link cascade) are NOT
     * here; they remain in {@see saveSourceOrm()} and their config paths are excluded from batching.
     *
     * @param mixed $source
     * @param string $confPath
     * @return void
     * @throws ReflectionException
     */
    public function formatSourceColumns($source, $confPath)
    {
        if ($confPath == ReplLscValidationPeriodTask::CONFIG_PATH) {
            $source->setData(
                LSCValidationPeriod::TIME_WITHIN_BOUNDS,
                $source->getData(LSCValidationPeriod::TIME_WITHIN_BOUNDS) ?? true
            );

            if (!empty($source->getData(LSCValidationPeriod::OFFER_STARTING_TIME))) {
                $source->setData(
                    LSCValidationPeriod::OFFER_STARTING_TIME,
                    '1900-01-01T' . $source->getData(LSCValidationPeriod::OFFER_STARTING_TIME)
                );
            }

            if (!empty($source->getData(LSCValidationPeriod::OFFER_ENDING_TIME))) {
                $source->setData(
                    LSCValidationPeriod::OFFER_ENDING_TIME,
                    '1900-01-01T' . $source->getData(LSCValidationPeriod::OFFER_ENDING_TIME)
                );
            }
        } elseif ($confPath == ReplLscHierarchyviewTask::CONFIG_PATH) {
            $value = $this->getConstantByIndex(HierarchyType::class, (int)$source->getData(HierarchyView::TYPE));
            $source->setData(HierarchyView::TYPE, $value);
        } elseif ($confPath == ReplLscHierarchynodeslinkviewTask::CONFIG_PATH) {
            $value = $this->getConstantByIndex(HierarchyLeafType::class, (int)$source->getData(HierarchyView::TYPE));
            $source->setData(HierarchyView::TYPE, $value);
        } elseif ($confPath == ReplLscPeriodicdiscviewTask::CONFIG_PATH) {
            $value1 = $this->getConstantByIndex(
                DiscountValueType::class,
                (int)$source->getData(PeriodicDiscView::DISCOUNT_TYPE)
            );
            $value2 = $this->getConstantByIndex(
                ReplDiscMemberType::class,
                (int)$source->getData(PeriodicDiscView::MEMBER_TYPE)
            );
            $value3 = $this->getConstantByIndex(
                ReplDiscountType::class,
                (int)$source->getData(PeriodicDiscView::TYPE)
            );
            $value4 = $this->getConstantByIndex(
                ReplDiscountLineType::class,
                (int)$source->getData(PeriodicDiscView::LINE_TYPE)
            );
            $source->setData(PeriodicDiscView::DISCOUNT_TYPE, $value1);
            $source->setData(PeriodicDiscView::MEMBER_TYPE, $value2);
            $source->setData(PeriodicDiscView::TYPE, $value3);
            $source->setData(PeriodicDiscView::LINE_TYPE, $value4);
        } elseif ($confPath == ReplLscWiItemBufferTask::CONFIG_PATH) {
            if (!empty($source->getData(LSCWIItemBuffer::ITEM_HTML))) {
                $source->setData(
                    LSCWIItemBuffer::ITEM_HTML,
                    base64_decode($source->getData(LSCWIItemBuffer::ITEM_HTML))
                );
            }
        } elseif ($confPath == ReplLscItemHtmlMlTask::CONFIG_PATH) {
            if (!empty($source->getData(LSCItemHTMLML::HTML))) {
                $source->setData(
                    LSCDataTranslation::TRANSLATION,
                    base64_decode($source->getData(LSCItemHTMLML::HTML))
                );
            }

            $source->setData(
                LSCDataTranslation::TRANSLATION_ID,
                LSR::SC_TRANSLATION_ID_ITEM_HTML
            );
            $source->setData(
                LSCDataTranslation::KEY,
                $source->getData(LSCItemHTMLML::ITEM_NO)
            );
            $source->setData(
                LSCDataTranslation::LANGUAGE_CODE,
                $source->getData(LSCItemHTMLML::LANGUAGE)
            );
        } elseif ($confPath == ReplLscOfferHtmlMlTask::CONFIG_PATH) {
            if (!empty($source->getData(\Ls\Omni\Client\CentralEcommerce\Entity\LSCOfferHTMLML::HTML))) {
                $source->setData(
                    LSCDataTranslation::TRANSLATION,
                    base64_decode($source->getData(\Ls\Omni\Client\CentralEcommerce\Entity\LSCOfferHTMLML::HTML))
                );
            }

            $source->setData(
                LSCDataTranslation::TRANSLATION_ID,
                LSR::SC_TRANSLATION_ID_DEAL_ITEM_HTML
            );
            $source->setData(
                LSCDataTranslation::KEY,
                $source->getData(\Ls\Omni\Client\CentralEcommerce\Entity\LSCOfferHTMLML::OFFER_NO)
            );
            $source->setData(
                LSCDataTranslation::LANGUAGE_CODE,
                $source->getData(\Ls\Omni\Client\CentralEcommerce\Entity\LSCOfferHTMLML::LANGUAGE)
            );
        } elseif ($confPath == ReplLscWiItemModifierTask::CONFIG_PATH) {
            $value1 = $this->getConstantByIndex(
                ItemModifierPriceType::class,
                (int)$source->getData(LSCWIItemModifier::PRICE_TYPE)
            );
            $value2 = $this->getConstantByIndex(
                ItemModifierPriceHandling::class,
                (int)$source->getData(LSCWIItemModifier::PRICE_HANDLING)
            );
            $value3 = $this->getConstantByIndex(
                ItemTriggerFunction::class,
                (int)$source->getData(LSCWIItemModifier::TRIGGER_FUNCTION)
            );
            $value4 = $this->getConstantByIndex(
                ItemModifierType::class,
                (int)$source->getData(LSCWIItemModifier::USAGE_SUBCATEGORY)
            );
            $value5 = $this->getConstantByIndex(
                ItemUsageCategory::class,
                (int)$source->getData(LSCWIItemModifier::USAGE_CATEGORY)
            );

            $source->setData(LSCWIItemModifier::PRICE_TYPE, $value1);
            $source->setData(LSCWIItemModifier::PRICE_HANDLING, $value2);
            $source->setData(LSCWIItemModifier::TRIGGER_FUNCTION, $value3);
            $source->setData(LSCWIItemModifier::USAGE_SUBCATEGORY, $value4);
            $source->setData(LSCWIItemModifier::USAGE_CATEGORY, $value5);
        } elseif ($confPath == ReplLscHierarchydealviewTask::CONFIG_PATH) {
            $value1 = $this->getConstantByIndex(
                HierarchyDealType::class,
                (int)$source->getData(HierarchyDealView::TYPE)
            );
            $source->setData(HierarchyDealView::TYPE, $value1);
        }
    }

    /**
     * Save new source or update already existing source via the ORM repository (legacy path).
     *
     * This is the original body of saveSource() and is used both for ineligible rows and as the
     * per-row replay fallback when a batch chunk fails to flush. Config-specific column formatting
     * is applied earlier in {@see saveSource()} via {@see formatSourceColumns()}.
     *
     * @param array $properties
     * @param mixed $source
     * @throws Exception
     */
    public function saveSourceOrm($properties, $source)
    {
        $isDeleted      = 0;
        $resetItemId    = null;
        $deletedImages  = [];
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
        // Config-specific source formatting is applied once in saveSource() before dispatch, via
        // formatSourceColumns(), so it is not repeated here. Only the delete-time side-effect
        // branches remain, and their config paths are excluded from batching by isBatchEligible().
        $confPath = $this->getConfigPath();
        if ($source->getIsDeleted() && $confPath == ReplEcommBasePricesTask::CONFIG_PATH) {
            // Find ItemId from the existing row for this scope + line + price list.
            $criteria = $this->getSearchCriteria();
            $criteria->addFilter('scope', $source->getScope());
            $criteria->addFilter('scope_id', $source->getScopeId());
            $criteria->addFilter('LineNumber', $source->getLineNo());
            $criteria->addFilter('PriceListCode', $source->getPriceListCode());
            $matchedRows = $this->getRepository()->getList($criteria->create())->getItems();

            if (!empty($matchedRows)) {
                $matchedRow = reset($matchedRows);
                $resetItemId = ($matchedRow && $matchedRow->getItemId()) ? $matchedRow->getItemId() : null;
            }
        } elseif ($source->getIsDeleted() && $confPath == ReplLscRetailImageLinkTask::CONFIG_PATH) {
            // Find records to update IsDeleted by scope + image id.

            if (!empty($source->getRecordId())) {
                $keyValue = preg_replace('/^.*:\s/', '', $source->getRecordId());
                $criteria = $this->getSearchCriteria();
                $criteria->addFilter('scope', $source->getScope());
                $criteria->addFilter('scope_id', $source->getScopeId());
                $criteria->addFilter('ImageId', $source->getImageId());
                $criteria->addFilter('KeyValue', $keyValue);
                $deletedImages = $this->getRepository()->getList($criteria->create())->getItems();

                if (!empty($deletedImages)) {
                    $this->updateDeletedImages($deletedImages);
                }
            }
        }
        $checksum = $this->getHashGivenString($source->getData());
        $uniqueAttributesHash = $this->generateIdentityValue($uniqueAttributes, $source, $properties);
        $entityArray = $this->checkEntityExistByAttributes(
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
        $this->applyStoreScopedColumns($entity, $source, $confPath);
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

            $this->applyDbColumnMapping($entity);
        }
        try {
            if (!empty($deletedImages) &&
                $source->getIsDeleted() &&
                $confPath == ReplLscRetailImageLinkTask::CONFIG_PATH
            ) { // Do not save  and reset entity, if IsDeleted is true.
                // The relevant items to delete are updated in function updateDeletedImages().
                $entity->setData([]);
            } else {
                $this->getRepository()->save($entity);
                $entity->setData([]);
            }

            if (!empty($resetItemId) &&
                $source->getIsDeleted() &&
                $confPath == ReplEcommBasePricesTask::CONFIG_PATH
            ) {
                //After deletion, reset processed status for all the records with the same ItemId for this scope,
                //so they can be re-processed in next sync price cron runs.
                $this->resetSyncPriceItems($source, $resetItemId);
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Apply the DB_TABLES_MAPPING column rename/format layer to an entity in place.
     *
     * Shared by {@see saveSourceOrm()} and {@see buildUpsertRow()} so the batch and ORM write paths
     * produce identical column data. For the flat table matching the entity, each source column is
     * copied to its mapped DB column name; offer start/end times are reformatted to H:i:s.
     *
     * @param mixed $entity
     * @return void
     * @throws \DateMalformedStringException
     */
    protected function applyDbColumnMapping($entity)
    {
        foreach (ReplicationHelper::DB_TABLES_MAPPING as $mapping) {
            if (ReplicationHelper::TABLE_NAME_PREFIX . $mapping['table_name'] !=
                $entity->getResource()->getMainTable()
            ) {
                continue;
            }
            foreach ($mapping['columns_mapping'] as $columnName => $columnMapping) {
                if (!$entity->hasData($columnName)) {
                    continue;
                }
                $value = $entity->getData($columnName);
                if (($columnName == 'offer_starting_time' || $columnName == 'offer_ending_time') &&
                    !empty($value)
                ) {
                    $timeObj = new \DateTime($value);
                    $value   = $timeObj->format('H:i:s');
                }
                $entity->setData(is_array($columnMapping) ? $columnMapping['name'] : $columnMapping, $value);
            }
            break;
        }
    }

    /**
     * Inject store-scoped enrichment columns that are not part of the source->column mapping.
     *
     * Currently only the sale-price view (repl_price) needs it: store_id is resolved from the
     * active web store for the row's scope. Shared by {@see saveSourceOrm()} and
     * {@see buildUpsertRow()} so the batch and ORM paths write the same store_id. For config paths
     * / tables without a store_id column the value is set transiently and dropped by the column
     * filter, matching the ORM path's _prepareDataForTable behaviour.
     *
     * @param mixed $entity
     * @param mixed $source
     * @param string $confPath
     * @return void
     * @throws NoSuchEntityException
     */
    protected function applyStoreScopedColumns($entity, $source, $confPath)
    {
        if ($confPath == ReplLscSalepriceviewTask::CONFIG_PATH) {
            $this->getLsrModel()->setStoreId($source->getScopeId());
            $entity->setData('store_id', $this->getLsrModel()->getActiveWebStore());
        }
    }

    /**
     * Whether the batch upsert write path is enabled for the given store via store config.
     *
     * Defaults to enabled (see config.xml); toggle off + cache flush restores the ORM path.
     *
     * @param mixed $storeId
     * @return bool
     */
    protected function isBatchUpsertEnabled($storeId)
    {
        return $this->scope_config->isSetFlag(
            LSR::SC_REPLICATION_BATCH_UPSERT_ENABLED,
            $this->defaultScope,
            $storeId
        );
    }

    /**
     * Eligibility gate: a row is batched iff ALL conditions hold; otherwise it takes the ORM path.
     *
     * Only genuine per-row side effects are excluded, all of them delete-time:
     *  - deletions keyed by the delete-specific unique attribute set
     *    (DELETE_JOB_CODE_UNIQUE_FIELD_ARRAY): their identity_value differs from the original row's,
     *    so an upsert would insert a duplicate deleted row instead of flipping the existing flag;
     *  - base-price deletions ({@see resetSyncPriceItems()} resets sibling rows by ItemId);
     *  - retail-image-link deletions ({@see updateDeletedImages()} cascades to related rows).
     * Pure column-formatting paths (enum decode, base64, validation-period times, data-translation
     * remaps) and the sale-price store_id enrichment ARE batched: formatSourceColumns() and
     * applyStoreScopedColumns() run for both paths and buildUpsertRow() reuses the same
     * entity-population code as the ORM path, so the written row is identical.
     *
     * @param mixed $source
     * @param string $confPath
     * @param mixed $storeId
     * @return bool
     */
    protected function isBatchEligible($source, $confPath, $storeId)
    {
        if (!$this->isBatchUpsertEnabled($storeId)) {
            // Presence flag, not a per-row counter: a disabled flag is a single configuration
            // state, not a mass-fallback incident.
            $this->fallbackCounts['flag_off'] = 1;
            return false;
        }

        if (!array_key_exists($confPath, ReplicationHelper::JOB_CODE_UNIQUE_FIELD_ARRAY)) {
            $this->countFallback('unmapped');
            return false;
        }

        $isDeleted = $source->getIsDeleted();

        if ($isDeleted && array_key_exists($confPath, ReplicationHelper::DELETE_JOB_CODE_UNIQUE_FIELD_ARRAY)) {
            $this->countFallback('delete_array');
            return false;
        }

        if ($isDeleted && $confPath === ReplEcommBasePricesTask::CONFIG_PATH) {
            $this->countFallback('base_price_delete');
            return false;
        }

        if ($isDeleted && $confPath === ReplLscRetailImageLinkTask::CONFIG_PATH) {
            $this->countFallback('image_link_delete');
            return false;
        }

        return true;
    }

    /**
     * Build the column => value map for one buffered row.
     *
     * Reuses the exact entity-population path the ORM save uses (property loop keyed by the
     * db-column mapping + {@see applyDbColumnMapping()}), then returns the entity's data array so
     * batch-written rows are byte-identical to the ORM path. No repository SELECT is performed —
     * the INSERT ... ON DUPLICATE KEY UPDATE resolves existence via the identity_value unique key.
     *
     * @param array $properties
     * @param mixed $source
     * @param mixed $checksum
     * @param mixed $identity
     * @return array
     * @throws \DateMalformedStringException
     */
    protected function buildUpsertRow($properties, $source, $checksum, $identity)
    {
        $entity = $this->getFactory()->create();
        $entity->setData('IsDeleted', $source->getIsDeleted() ? 1 : 0);
        $this->applyStoreScopedColumns($entity, $source, $this->getConfigPath());
        $entity->addData(
            [
                'checksum'                                 => $checksum,
                ReplicationHelper::UNIQUE_HASH_COLUMN_NAME => $identity,
                'scope'                                    => $source->getScope(),
                'scope_id'                                 => $source->getScopeId(),
            ]
        );
        foreach ($properties as $propertyIndex => $property) {
            $entity->setData($property, $source->getData($propertyIndex));
        }
        $this->applyDbColumnMapping($entity);

        return $entity->getData();
    }

    /**
     * Count one more row that fell back to the ORM path, keyed by reason.
     *
     * Maximum buffered rows per statement for a given column count, keeping the total placeholder
     * count under MySQL's limit (one extra placeholder is reserved for the updated_at literal bind).
     *
     * @param int $columnCount
     * @return int
     */
    protected function maxRowsPerChunk($columnCount)
    {
        if ($columnCount < 1) {
            return self::BATCH_CHUNK_SIZE;
        }

        return max(1, (int) floor(self::PLACEHOLDER_BUDGET / $columnCount));
    }

    /**
     * Build the chunked INSERT ... ON DUPLICATE KEY UPDATE statement and its positional binds.
     *
     * ON DUPLICATE KEY UPDATE refreshes every column to VALUES(col) EXCEPT identity_value (the
     * matched unique key), and additionally forces is_updated=1, is_failed=0, and updated_at=<now>
     * (bound). processed/created_at are never referenced and stay untouched.
     *
     * @param string $table
     * @param string[] $columns
     * @param array $rows
     * @return array
     */
    protected function buildUpsertSql($table, array $columns, array $rows)
    {
        $connection = $this->rep_helper->getConnection();

        $quotedColumns = array_map([$connection, 'quoteIdentifier'], $columns);
        $placeholders  = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
        $valuesClause  = implode(',', array_fill(0, count($rows), $placeholders));

        $updateParts = [];
        foreach ($columns as $column) {
            if ($column === ReplicationHelper::UNIQUE_HASH_COLUMN_NAME) {
                continue;
            }
            $quoted        = $connection->quoteIdentifier($column);
            $updateParts[] = $quoted . ' = VALUES(' . $quoted . ')';
        }
        $updateParts[] = $connection->quoteIdentifier('is_updated') . ' = 1';
        $updateParts[] = $connection->quoteIdentifier('is_failed') . ' = 0';
        $updateParts[] = $connection->quoteIdentifier('updated_at') . ' = ?';

        // Identifiers are quoted and every value is a positional bind; the raw statement is
        // required because insertOnDuplicate() cannot set literals on the update branch only.
        // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
        $sql = 'INSERT INTO ' . $connection->quoteIdentifier($table)
            . ' (' . implode(',', $quotedColumns) . ')'
            . ' VALUES ' . $valuesClause
            . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts);

        // Prepare each value exactly as the ORM save path does (AbstractDb::_prepareDataForTable ->
        // prepareColumnValue): empty nullable columns become NULL, numerics/dates are cast/formatted.
        $describe = $this->getTableDescription($table);

        $bind = [];
        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $value  = $row[$column] ?? null;
                $bind[] = isset($describe[$column])
                    ? $connection->prepareColumnValue($describe[$column], $value)
                    : $value;
            }
        }
        $bind[] = $this->rep_helper->getDateTime();

        return [$sql, $bind];
    }

    /**
     * Flush all buffered rows for a config path as one statement per chunk.
     *
     * On a chunk failure the chunk's rows are replayed through {@see saveSourceOrm()} row by row;
     * any row that still fails is logged and counted in {@see $flushFailures}.
     *
     * @param string $confPath
     * @param mixed $storeId
     * @return void
     */
    protected function flushBuffer($confPath, $storeId)
    {
        if (empty($this->upsertBuffer[$confPath])) {
            return;
        }

        $entries                       = $this->upsertBuffer[$confPath];
        $this->upsertBuffer[$confPath] = [];

        $table = $this->resolveUpsertTable($confPath);
        if (empty($table)) {
            $this->replayChunkViaOrm($entries, $confPath, 'no_table');
            return;
        }

        if (!$this->tableHasIdentityUniqueIndex($table)) {
            // Without a single-column UNIQUE on identity_value the upsert cannot dedupe and would
            // insert duplicates; fall back to the ORM path which resolves existence explicitly.
            $this->replayChunkViaOrm($entries, $confPath, 'no_identity_unique_index');
            return;
        }

        $columns = array_values(array_intersect(array_keys($entries[0]['row']), $this->getTableColumns($table)));
        if (empty($columns)) {
            $this->replayChunkViaOrm($entries, $confPath, 'no_columns');
            return;
        }

        $maxRows      = min(self::BATCH_CHUNK_SIZE, $this->maxRowsPerChunk(count($columns)));
        $chunks       = array_chunk($entries, $maxRows);
        $connection   = $this->rep_helper->getConnection();
        $start        = microtime(true);
        $rowsFlushed  = 0;
        $rowsReplayed = 0;

        foreach ($chunks as $chunk) {
            $rows         = array_column($chunk, 'row');
            [$sql, $bind] = $this->buildUpsertSql($table, $columns, $rows);

            try {
                $connection->query($sql, $bind);
                $rowsFlushed += count($chunk);
            } catch (Exception $e) {
                $this->logger->error(
                    'Batch upsert chunk flush failed, replaying via ORM',
                    [
                        'config_path' => $confPath,
                        'table'       => $table,
                        'store_id'    => $storeId,
                        'rows'        => count($chunk),
                        'exception'   => $e->getMessage(),
                    ]
                );
                $rowsReplayed += $this->replayChunkViaOrm($chunk, $confPath, 'chunk_flush_failed');
            }
        }

        $this->logger->info(
            'Batch upsert flush',
            [
                'config_path'   => $confPath,
                'table'         => $table,
                'store_id'      => $storeId,
                'scope'         => $this->defaultScope,
                'rows_upserted' => $rowsFlushed,
                'rows_replayed' => $rowsReplayed,
                'chunks'        => count($chunks),
                'duration_ms'   => (int) round((microtime(true) - $start) * 1000),
            ]
        );
    }

    /**
     * Replay a chunk's buffered rows through the ORM path, one row at a time.
     *
     * @param array $chunk
     * @param string $confPath
     * @param string $reason
     * @return int number of rows successfully replayed via the ORM path
     */
    protected function replayChunkViaOrm(array $chunk, $confPath, $reason)
    {
        $replayed = 0;
        foreach ($chunk as $entry) {
            try {
                $this->saveSourceOrm($entry['properties'], $entry['source']);
                $replayed++;
            } catch (Exception $e) {
                $this->flushFailures++;
                $this->logger->error(
                    'Batch upsert ORM replay failed',
                    [
                        'config_path' => $confPath,
                        'reason'      => $reason,
                        'exception'   => $e->getMessage(),
                    ]
                );
            }
        }

        return $replayed;
    }

    /**
     * Resolve the flat-table name for a config path, cached per config path.
     *
     * @param string $confPath
     * @return string
     */
    protected function resolveUpsertTable($confPath)
    {
        if (!array_key_exists($confPath, $this->upsertTableCache)) {
            $this->upsertTableCache[$confPath] = (string) $this->getFactory()
                ->create()
                ->getResource()
                ->getMainTable();
        }

        return $this->upsertTableCache[$confPath];
    }

    /**
     * Table description (column metadata) for a flat table, cached per table.
     *
     * @param string $table
     * @return array
     */
    protected function getTableDescription($table)
    {
        if (!array_key_exists($table, $this->upsertTableDescriptions)) {
            $this->upsertTableDescriptions[$table] = $this->rep_helper->getConnection()->describeTable($table);
        }

        return $this->upsertTableDescriptions[$table];
    }

    /**
     * Get the real column names of a flat table, cached per table.
     *
     * Real column names of a flat table, cached per table. Used to filter out entity properties
     * that are not actual DB columns before building the INSERT.
     *
     * @param string $table
     * @return string[]
     */
    protected function getTableColumns($table)
    {
        return array_keys($this->getTableDescription($table));
    }

    /**
     * Check if the table has a single-column UNIQUE index on identity_value, cached per table.
     *
     * Whether the table has a single-column UNIQUE index on identity_value, cached per table. This
     * is required for the ON DUPLICATE KEY UPDATE dedupe to be correct.
     *
     * @param string $table
     * @return bool
     */
    protected function tableHasIdentityUniqueIndex($table)
    {
        if (!array_key_exists($table, $this->identityUniqueIndexCache)) {
            $hasUnique = false;
            try {
                foreach ($this->rep_helper->getConnection()->getIndexList($table) as $index) {
                    $indexColumns = $index['COLUMNS_LIST'] ?? [];
                    if (($index['INDEX_TYPE'] ?? '') === 'unique' &&
                        count($indexColumns) === 1 &&
                        in_array(ReplicationHelper::UNIQUE_HASH_COLUMN_NAME, $indexColumns, true)
                    ) {
                        $hasUnique = true;
                        break;
                    }
                }
            } catch (Exception $e) {
                $hasUnique = false;
            }
            $this->identityUniqueIndexCache[$table] = $hasUnique;
        }

        return $this->identityUniqueIndexCache[$table];
    }

    /**
     * Increment the aggregated fallback-to-ORM counter for a reason.
     *
     * @param string $reason
     * @return void
     */
    protected function countFallback($reason)
    {
        if (!isset($this->fallbackCounts[$reason])) {
            $this->fallbackCounts[$reason] = 0;
        }
        $this->fallbackCounts[$reason]++;
    }

    /**
     * Emit a single aggregated line with fallback-to-ORM counts by reason for the run.
     *
     * @param string $confPath
     * @param mixed $storeId
     * @return void
     */
    protected function logFallbackSummary($confPath, $storeId)
    {
        if (empty($this->fallbackCounts)) {
            return;
        }

        $this->logger->info(
            'Batch upsert fallback counts',
            array_merge(
                ['config_path' => $confPath, 'store_id' => $storeId],
                $this->fallbackCounts
            )
        );
    }

    /**
     * Set IsDeleted true for variant images based on ImageId
     *
     * @param array $deletedImages
     * @return void'
     */
    public function updateDeletedImages($deletedImages)
    {
        if (!empty($deletedImages)) {
            try {
                foreach ($deletedImages as $deletedImage) {
                    $deletedImage->setIsDeleted(1);
                    $deletedImage->setProcessed(0);
                    $this->getRepository()->save($deletedImage);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Reset price records by ItemId
     *
     * @param mixed $source
     * @param string $resetItemId
     * @return void
     */
    public function resetSyncPriceItems($source, $resetItemId)
    {
        $resetCriteria = $this->getSearchCriteria();
        $resetCriteria->addFilter('scope', $source->getScope());
        $resetCriteria->addFilter('scope_id', $source->getScopeId());
        $resetCriteria->addFilter('ItemId', $resetItemId);

        $rowsToReset = $this->getRepository()->getList($resetCriteria->create())->getItems();

        if (!empty($rowsToReset)) {
            try {
                foreach ($rowsToReset as $rowToReset) {
                    $rowToReset->setProcessed(0);
                    $this->getRepository()->save($rowToReset);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Get constant name by index
     *
     * @param string $class
     * @param int $index
     * @return string|null
     * @throws ReflectionException
     */
    public function getConstantByIndex(string $class, int $index) : ?string
    {
        $reflection = new ReflectionClass($class);

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
            $modelClass = $this->getModelName();
            // @codingStandardsIgnoreStart
            $this->properties = $this->getModelName()::getDbColumnsMapping();
            // @codingStandardsIgnoreEnd
        }
        return $this->properties;
    }

    /**
     * Check the Entity exist or not
     *
     * @param array $uniqueAttributes
     * @param mixed $source
     * @param int $uniqueAttributesHash
     * @param array $properties
     * @return mixed
     */
    public function checkEntityExistByAttributes(
        array $uniqueAttributes,
        $source,
        int $uniqueAttributesHash,
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
     * @return bool
     */
    public function isLastKeyAlwaysZero()
    {
        $noLastKeyConfigPaths = self::$no_lastkey_config_path;

        if (($key = array_search('ls_mag/replication/repl_inv_status', $noLastKeyConfigPaths)) !== false) {
            unset($noLastKeyConfigPaths[$key]);
        }

        return in_array($this->getConfigPath(), $noLastKeyConfigPaths);
    }

    /**
     * Get last key
     *
     * @param string $storeId
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
     * @param string $storeId
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
     * @param string $storeId
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
     * @param string $lastKey
     * @param string $storeId
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
     * @param LSR $lsr
     * @param string $storeId
     * @return int
     */
    public function getBatchSize($lsr, $storeId)
    {
        $batchSize = 100;
        $isBatchSizeSet = $lsr->getGivenConfigInGivenScope(
            LSR::SC_REPLICATION_DEFAULT_BATCHSIZE,
            $this->defaultScope,
            $storeId
        );
        if ($isBatchSizeSet && is_numeric($isBatchSizeSet)) {
            $batchSize = $isBatchSizeSet;
        }

        return $batchSize;
    }

    /**
     * Get WebStore ID
     *
     * @param LSR $lsr
     * @param string $storeId
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
     * @param LSR $lsr
     * @param string $storeId
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
        $lastKey = $this->getLastKey($storeId);
        $lastEntryNo = $this->getLastEntryNo($storeId);
        $batchSize = $this->getBatchSize($lsr, $storeId);
        $webStoreID = $this->getWebStoreId($lsr, $storeId);
        $baseUrl = $this->getBaseUrl($lsr, $storeId);

        return [$lastKey ?? '', true, $batchSize, $webStoreID, $lastEntryNo ?? 0, $baseUrl, ''];
    }

    /**
     * Make request Fetch Data for given store
     *
     * @param string $storeId
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

                if ($this->isLastKeyAlwaysZero()) {
                    return;
                }
                if ($lastEntryNo === 0) {
                    $lastEntryNo = (int)$lastKey;
                    $lastKey = "";
                }
            }

            $request = $this->makeRequest(
                '',
                [],
                '',
                $fullRepl,
                (int)$batchSize,
                $webStoreID,
                (int)$lastEntryNo,
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
     * @param mixed $request
     * @param string $storeId
     */
    public function processResponseGivenRequest($request, $storeId)
    {
        // Reset per-run batch state at the top to guard against a leaked buffer from a prior
        // aborted run on a shared task instance.
        $confPath             = $this->getConfigPath();
        $this->upsertBuffer   = [];
        $this->flushFailures  = 0;
        $this->fallbackCounts = [];

        try {
            $properties = $this->getProperties();
            $response = $request->execute();
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
                        try {
                            foreach ($result as $source) {
                                //TODO need to understand this before we modify it.
                                $source->setScope($this->defaultScope)
                                    ->setScopeId($storeId);

                                $this->saveSource($properties, $source);
                            }
                            $this->updateSuccessStatus($storeId);
                        } finally {
                            // Always drain buffered rows, even if the row loop aborted mid-way.
                            $this->flushBuffer($confPath, $storeId);
                        }
                    }
                }

                if ($remaining == 0) {
                    $this->cronStatus = true;
                }
                // If any row could not be persisted by either path, do not mark the job complete so
                // LS Central re-pulls on the next tick.
                if ($this->flushFailures > 0) {
                    $this->cronStatus = false;
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
        } finally {
            // Batch diagnostics are emitted regardless of whether response processing completed or
            // aborted mid-way, so a partial/failed run is still observable.
            $this->logFallbackSummary($confPath, $storeId);
            $this->logger->info(
                'Batch upsert run summary',
                [
                    'config_path'    => $confPath,
                    'store_id'       => $storeId,
                    'fallback_rows'  => array_sum($this->fallbackCounts),
                    'flush_failures' => $this->flushFailures,
                    'cron_status'    => $this->cronStatus,
                ]
            );
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
                $confPath == ReplLscItemHtmlMlTask::CONFIG_PATH ||
                $confPath == ReplLscOfferHtmlMlTask::CONFIG_PATH
            ) {
                $this->defaultScope = ScopeInterface::SCOPE_STORES;
            }
        }
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
        array  $connectionParams = [],
        string $companyName = '',
        bool   $fullRepl = false,
        int    $batchSize = 100,
        string $storeNo = '',
        int    $lastEntryNo = 0,
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
