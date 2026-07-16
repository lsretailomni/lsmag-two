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
    /**
     * Default number of buffered rows flushed per INSERT ... ON DUPLICATE KEY UPDATE statement.
     */
    public const BATCH_CHUNK_SIZE = 1000;

    /**
     * Soft ceiling on bound placeholders per statement. MySQL's hard limit is 65,535; staying
     * well under it keeps wide tables (e.g. repl_item, repl_discount) safe.
     */
    private const PLACEHOLDER_BUDGET = 60000;

    /** @var array */
    private static $bypass_methods = ['getMaxKey', 'getLastKey', 'getRecordsRemaining'];

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
     * Pending batch rows keyed by config path. Each entry holds the column=>value map plus the
     * originating $source and $properties so a failed chunk can be replayed through the ORM path.
     *
     * @var array<string, array<int, array{row: array, source: mixed, properties: array}>>
     */
    protected $upsertBuffer = [];

    /**
     * Resolved flat-table name keyed by config path.
     *
     * @var array<string, string>
     */
    protected $upsertTableCache = [];

    /**
     * Cached table description (column metadata) keyed by table name. Used both to filter out
     * entity properties that are not real DB columns and to prepare bind values exactly as the
     * ORM path does (empty nullable => NULL, type casts) via AdapterInterface::prepareColumnValue.
     *
     * @var array<string, array>
     */
    protected $upsertTableDescriptions = [];

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
     * @var array<string, int>
     */
    protected $fallbackCounts = [];

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
     * @throws NoSuchEntityException
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
     * Execute the functionality manually from admin
     *
     * @param mixed $storeData
     * @return array
     * @throws ReflectionException|NoSuchEntityException
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
     * Dispatcher: eligible rows are buffered for a chunked INSERT ... ON DUPLICATE KEY UPDATE;
     * everything else falls back to the unchanged ORM path ({@see saveSourceOrm()}).
     *
     * @param array $properties
     * @param mixed $source
     * @throws Exception
     */
    public function saveSource($properties, $source)
    {
        $this->bufferOrSave($properties, $source, $this->getConfigPath(), $source->getScopeId());
    }

    /**
     * Decide whether a row is batched or written immediately via the ORM path.
     *
     * @param array $properties
     * @param mixed $source
     * @param string $confPath
     * @param mixed $storeId
     * @return void
     * @throws Exception
     */
    protected function bufferOrSave($properties, $source, $confPath, $storeId)
    {
        // Apply any config-specific source formatting exactly once, before the batch/ORM split,
        // so a batched row and a possible ORM replay of it never double-apply the transform.
        $this->formatSourceColumns($source, $confPath);

        if (!$this->isBatchEligible($source, $confPath, $storeId)) {
            $this->saveSourceOrm($properties, $source);
            return;
        }

        try {
            $uniqueAttributes     = ReplicationHelper::JOB_CODE_UNIQUE_FIELD_ARRAY[$confPath];
            $checksum             = $this->getHashGivenString($source);
            $uniqueAttributesHash = $this->generateIdentityValue($uniqueAttributes, $source);
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

        $columnCount = count($row);
        $chunkLimit  = min(self::BATCH_CHUNK_SIZE, $this->maxRowsPerChunk($columnCount));

        if (count($this->upsertBuffer[$confPath]) >= $chunkLimit) {
            $this->flushBuffer($confPath, $storeId);
        }
    }

    /**
     * Apply config-specific in-place column formatting to a source row before it is persisted.
     *
     * Currently only repl_discount_validation needs it: its start/end date and time columns are
     * converted to the current time zone. Called once by {@see bufferOrSave()} before the
     * batch/ORM split, so the transform is applied exactly once even when a buffered row is later
     * replayed through the ORM path on a flush failure. Because it only rewrites non-key columns
     * (the unique key is nav_id + scope_id), the row remains batchable via the upsert.
     *
     * @param mixed $source
     * @param string $confPath
     * @return void
     * @throws \DateMalformedStringException
     */
    protected function formatSourceColumns($source, $confPath)
    {
        if ($confPath === 'ls_mag/replication/repl_discount_validation') {
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
    }

    /**
     * Save new source or update already existing source via the ORM repository (legacy path).
     *
     * This is the original body of {@see saveSource()} and is used both for ineligible rows and as
     * the per-row replay fallback when a batch chunk fails to flush. Config-specific column
     * formatting is applied earlier in {@see bufferOrSave()} via {@see formatSourceColumns()}.
     *
     * @param array $properties
     * @param mixed $source
     * @throws Exception
     */
    public function saveSourceOrm($properties, $source)
    {
        if ($source->getIsDeleted()) {
            $uniqueAttributes = (array_key_exists(
                $this->getConfigPath(),
                ReplicationHelper::DELETE_JOB_CODE_UNIQUE_FIELD_ARRAY
            )) ?
                ReplicationHelper::DELETE_JOB_CODE_UNIQUE_FIELD_ARRAY[$this->getConfigPath()] :
                ReplicationHelper::JOB_CODE_UNIQUE_FIELD_ARRAY[$this->getConfigPath()];
        } else {
            $uniqueAttributes = ReplicationHelper::JOB_CODE_UNIQUE_FIELD_ARRAY[$this->getConfigPath()];
        }
        // Config-specific column formatting (e.g. repl_discount_validation date/time conversion)
        // is applied once in bufferOrSave() before dispatch, so it is not repeated here.
        $confPath = $this->getConfigPath();

        if ($source->getIsDeleted() && $confPath == ReplEcommBasePricesTask::CONFIG_PATH) {
            // Find ItemId from the existing row for this scope + line + price list.
            $criteria = $this->getSearchCriteria();
            $criteria->addFilter('scope', $source->getScope());
            $criteria->addFilter('scope_id', $source->getScopeId());
            $criteria->addFilter('LineNumber', $source->getLineNumber());
            $criteria->addFilter('PriceListCode', $source->getPriceListCode());
            $matchedRows = $this->getRepository()->getList($criteria->create())->getItems();

            if (!empty($matchedRows)) {
                $matchedRow = reset($matchedRows);
                $resetItemId = ($matchedRow && $matchedRow->getItemId()) ? $matchedRow->getItemId() : null;
            }
        }
        $checksum             = $this->getHashGivenString($source);
        $uniqueAttributesHash = $this->generateIdentityValue($uniqueAttributes, $source);
        $entityArray          = $this->checkEntityExistByAttributes($uniqueAttributes, $source, $uniqueAttributesHash);

        if (!empty($entityArray)) {
            $entity = reset($entityArray);
            $entity->setIsUpdated(1);
            $entity->setIsFailed(0);
            $entity->setUpdatedAt($this->rep_helper->getDateTime());
            $entity->setIdentityValue($uniqueAttributesHash);
        } else {
            $entity = $this->getFactory()->create();
        }

        if ($entity->getChecksum() != $checksum) {
            $entity->setChecksum($checksum);
            $entity->setIdentityValue($uniqueAttributesHash);

            foreach ($properties as $property) {
                if ($property === 'nav_id') {
                    $setMethod = 'setNavId';
                    $getMethod = 'getId';
                } else {
                    $fieldNameCapitalized = str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
                    $setMethod            = "set$fieldNameCapitalized";
                    $getMethod            = "get$fieldNameCapitalized";
                }
                if ($entity && $source && method_exists($entity, $setMethod) && method_exists($source, $getMethod)) {
                    $entity->{$setMethod}($source->{$getMethod}());
                }
            }
        }

        try {
            $this->getRepository()->save($entity);
            if (!empty($resetItemId) &&
                $source->getIsDeleted() &&
                $confPath == ReplEcommBasePricesTask::CONFIG_PATH
            ) {
                //After deletion, reset processed status for all the records with the same ItemId for this scope,
                //so they can be re-processed in next sync price cron runs.
                $this->resetSyncPriceItems($source, $resetItemId);
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
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
            $this->countFallback('repl_price_delete');
            return false;
        }

        return true;
    }

    /**
     * Build the column => value map for one buffered row.
     *
     * Reuses the exact property loop the ORM path uses: nav_id maps to getId(), every other
     * property maps to its get<CamelCase>() accessor, and a column is included only when the
     * source exposes that getter. Bookkeeping columns (is_updated/is_failed/processed/created_at/
     * updated_at) have no source getter and are therefore omitted, taking schema defaults on
     * insert and explicit literals on update. scope/scope_id arrive through the loop. checksum and
     * identity_value are overlaid.
     *
     * @param array $properties
     * @param mixed $source
     * @param int $checksum
     * @param int $identity
     * @return array<string, mixed>
     */
    protected function buildUpsertRow($properties, $source, $checksum, $identity)
    {
        $row = [];

        foreach ($properties as $property) {
            if ($property === 'nav_id') {
                $getMethod = 'getId';
            } else {
                $fieldNameCapitalized = str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
                $getMethod            = "get$fieldNameCapitalized";
            }

            if (method_exists($source, $getMethod)) {
                $row[$property] = $source->{$getMethod}();
            }
        }

        $row[ReplicationHelper::UNIQUE_HASH_COLUMN_NAME] = $identity;
        $row['checksum']                                 = $checksum;

        return $row;
    }

    /**
     * Maximum buffered rows allowed per statement for a given column count, keeping the total
     * placeholder count under MySQL's limit (one extra placeholder is reserved for the
     * updated_at literal bind).
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
     * INSERT column list = the buffered row columns (data + scope/scope_id + checksum +
     * identity_value). ON DUPLICATE KEY UPDATE refreshes every column to VALUES(col) EXCEPT
     * identity_value (the matched unique key), and additionally forces the literals is_updated=1,
     * is_failed=0, and updated_at=<getDateTime()> (bound). processed/created_at are never
     * referenced and stay untouched.
     *
     * @param string $table
     * @param string[] $columns
     * @param array $rows
     * @return array{0: string, 1: array}
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

        // Prepare each value exactly as the ORM save path does (AbstractDb::_prepareDataForTable
        // -> prepareColumnValue): empty nullable string columns become NULL, numerics/dates are
        // cast/formatted. This keeps batch-written rows byte-identical to the ORM path.
        $describe = $this->getTableDescription($table);

        $bind = [];
        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $value = $row[$column] ?? null;
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

        $entries = $this->upsertBuffer[$confPath];
        $this->upsertBuffer[$confPath] = [];

        $table = $this->resolveUpsertTable($confPath);
        if (empty($table)) {
            $this->replayChunkViaOrm($entries, $confPath, 'no_table');
            return;
        }

        $columns      = array_values(array_intersect(array_keys($entries[0]['row']), $this->getTableColumns($table)));
        $maxRows      = min(self::BATCH_CHUNK_SIZE, $this->maxRowsPerChunk(count($columns)));
        $chunks       = array_chunk($entries, $maxRows);
        $start        = microtime(true);
        $rowsFlushed  = 0;
        $rowsReplayed = 0;
        $connection   = $this->rep_helper->getConnection();

        foreach ($chunks as $chunk) {
            $rows = array_column($chunk, 'row');
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
     * @return array<string, array>
     */
    protected function getTableDescription($table)
    {
        if (!array_key_exists($table, $this->upsertTableDescriptions)) {
            $this->upsertTableDescriptions[$table] = $this->rep_helper->getConnection()->describeTable($table);
        }

        return $this->upsertTableDescriptions[$table];
    }

    /**
     * Get the main entity table columns
     *
     * Real column names of a flat table, cached per table. Used to filter out entity
     * properties that are not actual DB columns before building the INSERT.
     *
     * @param string $table
     * @return string[]
     */
    protected function getTableColumns($table)
    {
        return array_keys($this->getTableDescription($table));
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
     *
     * @param array $uniqueAttributes
     * @param mixed $source
     * @param string $uniqueAttributesHash
     * @return mixed
     */
    public function checkEntityExistByAttributes($uniqueAttributes, $source, $uniqueAttributesHash)
    {
        $criteria = $this->getSearchCriteria();
        $criteria->addFilter(ReplicationHelper::UNIQUE_HASH_COLUMN_NAME, $uniqueAttributesHash);

        $result = $this->getRepository()->getList($criteria->create())->getItems();

        if (empty($result)) {
            $criteria = $this->getSearchCriteria();

            foreach ($uniqueAttributes as $attribute) {
                $sourceValue = $this->getAttributeValue($attribute, $source);

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
     * @return int
     */
    public function generateIdentityValue($uniqueAttributes, $source)
    {
        $uniqueAttributesHash = [];

        foreach ($uniqueAttributes as $index => $attribute) {
            $sourceValue            = $this->getAttributeValue($attribute, $source);
            $uniqueAttributesHash[] = ($sourceValue !== "" ? $sourceValue : $attribute) . '#' . $index;
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
     * @param string $storeId
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
     * Get max key
     *
     * @param string $storeId
     * @return mixed|null
     */
    public function getMaxKey($storeId)
    {
        $lsrModel = $this->getLsrModel();

        return $lsrModel->getConfigValueFromDb(
            $this->getConfigPathMaxKey(),
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
     * Persist max key
     *
     * @param string $maxKey
     * @param string $storeId
     * @return void
     */
    public function persistMaxKey($maxKey, $storeId)
    {
        $this->rep_helper->updateConfigValue($maxKey, $this->getConfigPathMaxKey(), $storeId, $this->defaultScope);
    }

    /**
     * Iterate through result set
     *
     * @param mixed $result
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
     * Get LSR model
     *
     * We cant use the DI method to get LSR model in here,
     * so we need to use the object manager approach to get LSR model.
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
     * @return int|string
     */
    public function getBatchSize($lsr, $storeId)
    {
        $batchSize      = 100;
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
     * @param string $storeId
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

                if ($this->isLastKeyAlwaysZero($storeId)) {
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
     * @param mixed $request
     * @param string $storeId
     * @param mixed $isFirstTime
     */
    public function processResponseGivenRequest($request, $storeId, $isFirstTime = 1)
    {
        // Reset per-run batch state at the top to guard against a leaked buffer from a prior
        // aborted run on a shared task instance.
        $confPath             = $this->getConfigPath();
        $this->upsertBuffer   = [];
        $this->flushFailures  = 0;
        $this->fallbackCounts = [];

        try {
            $properties       = $this->getProperties();
            $response         = $request->execute();
            $this->cronStatus = false;

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
                        try {
                            foreach ($traversable as $source) {
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
                // If any row could not be persisted by either path, do not mark the job complete
                // so LS Central re-pulls on the next tick.
                if ($this->flushFailures > 0) {
                    $this->cronStatus = false;
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
        } finally {
            // Batch diagnostics are emitted regardless of whether response processing
            // completed or aborted mid-way, so a partial/failed run is still observable.
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
     * Emit a single aggregated info line with fallback-to-ORM counts by reason for the run.
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
     * Set default scope
     */
    public function setDefaultScope()
    {
        $lsr = $this->getLsrModel();

        if ($lsr->isSSM()) {
            $this->defaultScope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        } else {
            $confPath = $this->getConfigPath();

            if ($confPath == ReplEcommDataTranslationTask::CONFIG_PATH ||
                $confPath == ReplEcommDataTranslationLangCodeTask::CONFIG_PATH ||
                $confPath == ReplEcommHtmlTranslationTask::CONFIG_PATH ||
                $confPath == ReplEcommDealHtmlTranslationTask::CONFIG_PATH
            ) {
                $this->defaultScope = ScopeInterface::SCOPE_STORES;
            }
        }
    }

    /**
     * Execute discount replication for central type saas or on-prem
     *
     * @param LSR $lsr
     * @param mixed $store
     * @param string $scope
     * @return bool
     * @throws NoSuchEntityException
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
