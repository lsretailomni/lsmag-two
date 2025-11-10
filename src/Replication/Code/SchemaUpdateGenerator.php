<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use DOMDocument;
use Laminas\Code\Generator\GeneratorInterface;
use Laminas\Code\Reflection\ClassReflection;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\ExpressionConverter;
use Magento\Framework\Module\Dir\Reader;
use ReflectionException;

class SchemaUpdateGenerator implements GeneratorInterface
{
    /** @var array List of Replication Tables indexer for search */
    public static $indexerColumnLists = [
        'ls_replication_repl_attribute' => [
            'Code',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_attribute_option_value' => [
            'Code',
            'Sequence',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_attribute_value' => [
            'Code',
            'LinkField1',
            'LinkField2',
            'LinkField3',
            'Sequence',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_barcode' => [
            'nav_id',
            'ItemId',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_countryview' => [
            'name',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_currency' => [
            'CurrencyCode',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_currency_exchange_rate' => [
            'currency_code',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_customer' => [
            'AccountNumber',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_lsc_data_translation' => [
            'translation_id',
            'key',
            'language_code',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_data_translation_lang_code' => [
            'code',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_mag/replication/repl_periodicdiscview' => [
            'offer_no',
            'customer_disc_group',
            'no',
            'unit_of_measure',
            'variant_code',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_lsc_validation_period' => [
            'nav_id',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_extended_variant_value' => [
            'Code',
            'FrameworkCode',
            'ItemId',
            'Value',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_hierarchy' => [
            'nav_id',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_hierarchy_leaf' => [
            'nav_id',
            'NodeId',
            'HierarchyCode',
            'Type',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_hierarchy_node' => [
            'ParentNode',
            'HierarchyCode',
            'nav_id',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_image' => [
            'nav_id',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_image_link' => [
            'ImageId',
            'TableName',
            'KeyValue',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_item' => [
            'nav_id',
            'ItemCategoryCode',
            'ProductGroupId',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_item_category' => [
            'nav_id',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_item_unit_of_measure' => [
            'Code',
            'ItemId',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_item_variant_registration' => [
            'ItemId',
            'VariantId',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_vendoritemview' => [
            'vendorno',
            'itemno',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_price' => [
            'ItemId',
            'VariantId',
            'StoreId',
            'QtyPerUnitOfMeasure',
            'UnitOfMeasure',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_inv_status' => [
            'ItemId',
            'VariantId',
            'StoreId',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_lsc_retail_product_group' => [
            'code',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_shipping_agent' => [
            'name',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_storeview' => [
            'no',
            'scope_id'
        ],
        'ls_replication_repl_tenderview' => [
            'code',
            'scope_id'
        ],
        'ls_replication_repl_unit_of_measure' => [
            'nav_id',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_vendor' => [
            'nav_id',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_hierarchydeallineview' => [
            'offer_no',
            'item_no',
            'offer_line_no',
            'unit_of_measure',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_hierarchydealview' => [
            'offer_no',
            'no',
            'line_no',
            'unit_of_measure',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_lsc_wi_item_recipe_buffer' => [
            'parent_item_no',
            'no',
            'unit_of_measure_code',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ],
        'ls_replication_repl_lsc_wi_item_modifier' => [
            'parent_item_no',
            'variant_code',
            'subcode',
            'infocode_code',
            'unit_of_measure',
            'scope_id',
            'processed',
            'is_updated',
            'IsDeleted'
        ]
    ];

    /**
     * @param array $replicationOperations
     */
    public function __construct(public array $replicationOperations)
    {
    }

    /**
     * Create dynamic db_schema.xml file and save to etc folder of Replication Module
     *
     * @return void
     * @throws ReflectionException
     * @throws \DOMException
     */
    public function generate()
    {
        $dom               = new DOMDocument('1.0');
        $dom->formatOutput = true;
        $schema            = $dom->createElement('schema');
        $schema->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $schema->setAttribute('xsi:noNamespaceSchemaLocation', 'urn:magento:framework:Setup/Declaration/Schema/etc/schema.xsd');
        $tables = [];
        $dbTablesMapping = ReplicationHelper::DB_TABLES_MAPPING;
        foreach ($this->replicationOperations as $replicationOperation) {
            $columnMappings = null;
            $tableName = $replicationOperation->getTableName();
            $tableIdColumnName = $replicationOperation->getTableColumnId();

            if (isset($dbTablesMapping[$tableName])) {
                $mappings = $dbTablesMapping[$tableName];
                $tableName = ReplicationHelper::TABLE_NAME_PREFIX . $mappings['table_name'];
                $columnMappings = $mappings['columns_mapping'];
                $tableIdColumnName = $mappings['table_name'] . "_id";
            } else {
                continue;
            }

            $tableIncludedInIndex = array_key_exists($tableName, self::$indexerColumnLists);
            if (!in_array($tableName, $tables)) {
                $table = $dom->createElement('table');
                $table->setAttribute('name', $tableName);
                $table->setAttribute('resource', 'default');
                $table->setAttribute('engine', 'innodb');
                $table->setAttribute('comment', $replicationOperation->getName());
                $column = $dom->createElement('column');
                $column->setAttribute('xsi:type', 'int');
                $column->setAttribute('name', $tableIdColumnName);
                $column->setAttribute('padding', '10');
                $column->setAttribute('unsigned', 'false');
                $column->setAttribute('nullable', 'false');
                $column->setAttribute('identity', 'true');
                $column->setAttribute('comment', $tableIdColumnName);
                $table->appendChild($column);
                $extraColumnsArray = [
                    [
                        'name' => 'scope',
                        'field_type' => 'varchar',
                        'default' => '',
                        'length' => '200',
                        'comment' => 'Record Scope'
                    ],
                    [
                        'name' => 'scope_id',
                        'field_type' => 'int',
                        'default' => '',
                        'comment' => 'Record Scope ID'
                    ],
                    [
                        'name' => 'IsDeleted',
                        'field_type' => 'boolean',
                        'default' => '0',
                        'comment' => 'Flag to check if data is deleted in Central. 0 means not deleted & 1 means deleted in Central.'
                    ],
                    [
                        'name' => 'processed',
                        'field_type' => 'boolean',
                        'default' => '0',
                        'comment' => 'Flag to check if data is already copied into Magento. 0 means needs to be copied into Magento tables & 1 means already copied'
                    ],
                    [
                        'name' => 'is_updated',
                        'field_type' => 'boolean',
                        'default' => '0',
                        'comment' => 'Flag to check if data is already updated from Omni into Magento. 0 means already updated & 1 means needs to be updated into Magento tables'
                    ],
                    [
                        'name' => 'is_failed',
                        'field_type' => 'boolean',
                        'default' => '0',
                        'comment' => 'Flag to check if data is already added from Flat into Magento successfully or not. 0 means already added successfully & 1 means failed to add successfully into Magento tables'
                    ],
                    [
                        'name' => 'identity_value',
                        'field_type' => 'varchar',
                        'default' => '',
                        'length' => '200',
                        'comment' => 'Hash value of all unique columns'
                    ],
                    [
                        'name' => 'checksum',
                        'field_type' => 'text',
                        'default' => '',
                        'comment' => 'Checksum'
                    ],
                    [
                        'name' => 'processed_at',
                        'field_type' => 'timestamp',
                        'default' => '',
                        'comment' => 'Processed At'
                    ],
                    [
                        'name' => 'created_at',
                        'field_type' => 'timestamp',
                        'default' => 'CURRENT_TIMESTAMP',
                        'comment' => 'Created At'
                    ],
                    [
                        'name' => 'updated_at',
                        'field_type' => 'timestamp',
                        'default' => 'CURRENT_TIMESTAMP',
                        'comment' => 'Updated At'
                    ]
                ];


                if ($tableName == 'ls_replication_repl_item_variant') {
                    $extraColumnsArray[] =  [
                        'name'       => 'ready_to_process',
                        'field_type' => 'boolean',
                        'default'    => '0',
                        'comment'    => 'Flag to check if data is ready to be processed. 0 means not yet ready & 1 means already ready'
                    ];
                }

                $reflectedEntity     = new ClassReflection($replicationOperation->getOmniEntityFqn());
                $defaultColumnsArray = [];
                $constants = $reflectedEntity->getConstants();
                $methods = $reflectedEntity->getMethods(\ReflectionMethod::IS_PUBLIC);

                $originalClass = $replicationOperation->getOmniEntityFqn();
                $objectManager = $this->getObjectManager();
                $mapping = $objectManager->get($originalClass);
                $dbColumnsMapping = $mapping->getDbColumnsMapping();
                if ($tableName == 'ls_replication_repl_hierarchy_leaf')
                {
                    $x = 1;
                }
                foreach ($methods as $method) {
                    if ($method->getDeclaringClass()->getName() !== $originalClass ||
                        $method->getName() == 'getDbColumnsMapping'
                    ) {
                        continue;
                    }
                    $name = $method->getName();
                    $body = $method->getBody();

                    if (str_starts_with($name, 'get')) {
                        preg_match('/self::([A-Z0-9_]+)/', $body, $matches);

                        if (!empty($matches)) {
                            $constName = $matches[1];
                            $propertyName = $constants[$constName];
                            if (isset($dbColumnsMapping[$propertyName])) {
                                $propertyName = $dbColumnsMapping[$propertyName];
                            }
                            if (!isset($defaultColumnsArray[$constName])) {
                                $defaultColumnsArray[$constName] = [
                                    'name' => $propertyName,
                                    'comment' => $propertyName
                                ];
                            }

                            $returnType = $method->getReturnType();
                            $returnTypeName = '';
                            if ($returnType instanceof \ReflectionNamedType) {
                                $returnTypeName = $returnType->getName();
                                $nullablePrefix = $returnType->allowsNull() ? '?' : '';
                            }
                            $fieldType = '';
                            $default = '';
                            if ($returnTypeName == 'int') {
                                $fieldType = 'int';
                            } elseif ($returnTypeName == 'float') {
                                $fieldType = 'decimal';
                            } elseif ($returnTypeName == 'bool') {
                                $fieldType = 'boolean';
                                $default   = '0';
                            } else {
                                $lower_name = strtolower($name);
                                if (strpos($lower_name, 'image64') === false) {
                                    $fieldType = 'text';
                                } else {
                                    $fieldType = 'blob';
                                }
                            }
                            $defaultColumnsArray[$constName]['field_type'] = $fieldType;
                            $defaultColumnsArray[$constName]['default'] = $default;
                        }
                    }
                }

                array_multisort(array_column($defaultColumnsArray, 'name'), SORT_ASC, $defaultColumnsArray);

                $allColumnsArray = array_merge($defaultColumnsArray, $extraColumnsArray);
                foreach ($allColumnsArray as $columnValue) {
                    $columnName = $columnValue['name'];
                    $columnType = $columnValue['field_type'];
                    if ($columnMappings) {
                        if (isset($columnMappings[$columnName])) {
                            if (isset($columnMappings[$columnName]['name']) &&
                                isset($columnMappings[$columnName]['type'])
                            ) {
                                $columnType = $columnMappings[$columnName]['type'];
                                $columnName = $columnMappings[$columnName]['name'];
                            } else {
                                $columnName = $columnMappings[$columnName];
                            }

                            if ($tableIncludedInIndex) {
                                if (in_array($columnName, self::$indexerColumnLists[$tableName])
                                    && $columnType == 'text'
                                ) {
                                    $columnType = 'varchar';
                                }
                            }
                        } else {
                            $isExtraColumns = false;
                            foreach ($extraColumnsArray as $extraColumn) {
                                if ($columnName == $extraColumn['name']) {
                                    $isExtraColumns = true;
                                    break;
                                }
                            }

                            if (!$isExtraColumns) {
                                continue;
                            }
                        }
                    }
                    $extraColumn = $dom->createElement('column');
                    $extraColumn->setAttribute('xsi:type', $columnType);
                    $extraColumn->setAttribute('name', $columnName);
                    if ($columnType == 'decimal') {
                        $extraColumn->setAttribute('scale', '4');
                        $extraColumn->setAttribute('precision', '20');
                    }
                    if ($columnType == 'int')
                        $extraColumn->setAttribute('padding', '11');
                    if ($columnType == 'varchar')
                        $extraColumn->setAttribute('length', '200');
                    if ($columnValue['default'] != '')
                        $extraColumn->setAttribute('default', $columnValue['default']);
                    if ($columnValue['name'] == 'created_at')
                        $extraColumn->setAttribute('on_update', 'false');
                    if ($columnValue['name'] == 'updated_at')
                        $extraColumn->setAttribute('on_update', 'true');
                    $extraColumn->setAttribute('nullable', 'true');
                    $extraColumn->setAttribute('comment', $columnValue['comment']);
                    $table->appendChild($extraColumn);
                }
                // for primary key
                $constraint = $dom->createElement('constraint');
                $constraint->setAttribute('xsi:type', 'primary');
                $constraint->setAttribute('referenceId', 'PRIMARY');
                $column = $dom->createElement('column');
                $column->setAttribute('name', $tableIdColumnName);
                $constraint->appendChild($column);
                $table->appendChild($constraint);

                //indexer based on the searchable column and add here
                /**
                 * this will be the final outcome
                 *  <index referenceId="CATALOG_PRODUCT_ENTITY_SKU" indexType="btree">
                 *       <column name="sku"/>
                 *  </index>
                 */
                if (array_key_exists($tableName, self::$indexerColumnLists)) {
                    $indexerColumns = self::$indexerColumnLists[$tableName];
                    if ($indexerColumns && !empty($indexerColumns)) {
                        foreach ($indexerColumns as $indexerColumn) {
                            $optimizedFieldName = $this->formatGivenValue(ucwords(strtolower($indexerColumn)));
                            $constName = str_replace(
                                ' ',
                                '_',
                                strtoupper(preg_replace('/\B([A-Z])/', '_$1', $optimizedFieldName))
                            );
                            $referenceId     = strtoupper(implode("_", array($tableName, $constName)));
                            $indexColumnNode = $dom->createElement('index');
                            $indexColumnNode->setAttribute('indexType', 'btree');
                            $indexColumnNode->setAttribute('referenceId', $referenceId);
                            $column = $dom->createElement('column');
                            $column->setAttribute('name', $indexerColumn);
                            $indexColumnNode->appendChild($column);
                            $table->appendChild($indexColumnNode);
                        }
                    }
                }

                //unique constraint based on the combination of column
                /**
                 * this will be the final outcome
                 * <constraint xsi:type="unique" referenceId="CATALOG_CATEGORY_PRODUCT_CATEGORY_ID_PRODUCT_ID">
                 *       <column name="category_id"/>
                 *       <column name="product_id"/>
                 *  </constraint>
                 */
                $keyToSearch = str_replace("ls_replication_", "ls_mag/replication/","$tableName");
                if (array_key_exists($keyToSearch, ReplicationHelper::JOB_CODE_UNIQUE_FIELD_ARRAY)) {
                    $uniqueColumns = ReplicationHelper::JOB_CODE_UNIQUE_FIELD_ARRAY[$keyToSearch];
                    if ($uniqueColumns && !empty($uniqueColumns)) {
                        $uniqueColumnNode = $dom->createElement('constraint');
                        $uniqueColumnNode->setAttribute('xsi:type', 'unique');
                        $fields      = ReplicationHelper::UNIQUE_HASH_COLUMN_NAME;
                        $prefix      = 'unq_';
                        $referenceId = strtoupper(ExpressionConverter::shortenEntityName(
                            $tableName . '_' . $fields,
                            $prefix
                        ));
                        $uniqueColumnNode->setAttribute('referenceId', $referenceId);
                        $column = $dom->createElement('column');
                        $column->setAttribute('name', ReplicationHelper::UNIQUE_HASH_COLUMN_NAME);
                        $uniqueColumnNode->appendChild($column);
                    }
                    $table->appendChild($uniqueColumnNode);
                }

                $schema->appendChild($table);
                array_push($tables, $tableName);
            }
        }

        $dom->appendChild($schema);
        $dom->save($this->getPath());
    }

    /**
     * Get db_schema.xml path
     *
     * @return string
     */
    public function getPath()
    {
        $objectManager = $this->getObjectManager();
        /** @var  Reader $dirReader */
        $dirReader = $objectManager->get('\Magento\Framework\Module\Dir\Reader');
        $basePath  = $dirReader->getModuleDir('', 'Ls_Replication');
        return $basePath . "/etc/db_schema.xml";
    }

    /**
     * Get formatted value
     *
     * @param string $value
     * @param string $replaceWith
     * @return string
     */
    public function formatGivenValue(string $value, string $replaceWith = ''): string
    {
        return trim(preg_replace('/[\/\[\]()$\-._%&]/', $replaceWith, $value));
    }

    /**
     * Get object Manager object
     *
     * @return ObjectManager
     */
    public function getObjectManager(): ObjectManager
    {
        return ObjectManager::getInstance();
    }
}
