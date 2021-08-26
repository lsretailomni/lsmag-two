<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use DOMDocument;
use Laminas\Code\Generator\GeneratorInterface;
use Laminas\Code\Reflection\ClassReflection;
use \Ls\Omni\Service\Metadata;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\ExpressionConverter;
use Magento\Framework\Module\Dir\Reader;
use ReflectionException;

/**
 * Class SchemaUpdateGenerator
 * @package Ls\Replication\Code
 */
class SchemaUpdateGenerator implements GeneratorInterface
{

    /** @var array List of Replication Tables indexer for search */
    public static $indexerColumnLists = [
        "ls_replication_repl_attribute"                  => [
            "Code",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_attribute_option_value"     => [
            "Code",
            "Sequence",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_attribute_value"            => [
            "Code",
            "LinkField1",
            "LinkField2",
            "LinkField3",
            "Sequence",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_barcode"                    => [
            "nav_id",
            "ItemId",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_country_code"               => [
            "Name",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_currency"                   => [
            "CurrencyCode",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_currency_exch_rate"         => [
            "CurrencyCode",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_customer"                   => [
            "AccountNumber",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_data_translation"           => [
            "TranslationId",
            "Key",
            "LanguageCode",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_data_translation_lang_code" => [
            "Code",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_discount"                   => [
            "ItemId",
            "LoyaltySchemeCode",
            "OfferNo",
            "ToDate",
            "StoreId",
            "VariantId",
            "MinimumQuantity",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_discount_validation"        => [
            "nav_id",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_extended_variant_value"     => [
            "Code",
            "FrameworkCode",
            "ItemId",
            "Value",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_hierarchy"                  => [
            "nav_id",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_hierarchy_leaf"             => [
            "nav_id",
            "NodeId",
            "HierarchyCode",
            "Type",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_hierarchy_node"             => [
            "ParentNode",
            "HierarchyCode",
            "nav_id",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_image"                      => [
            "nav_id",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_image_link"                 => [
            "ImageId",
            "TableName",
            "KeyValue",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_item"                       => [
            "nav_id",
            "ItemCategoryCode",
            "ProductGroupId",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_item_category"              => [
            "nav_id",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_item_unit_of_measure"       => [
            "Code",
            "ItemId",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_item_variant_registration"  => [
            "ItemId",
            "VariantId",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_loy_vendor_item_mapping"    => [
            "NavManufacturerId",
            "NavProductId",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_price"                      => [
            "ItemId",
            "VariantId",
            "StoreId",
            "QtyPerUnitOfMeasure",
            "UnitOfMeasure",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_inv_status"                 => [
            "ItemId",
            "VariantId",
            "StoreId",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_product_group"              => [
            "nav_id",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_shipping_agent"             => [
            "Name",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_store"                      => [
            "nav_id",
            "scope_id"
        ],
        "ls_replication_repl_store_tender_type"          => [
            "TenderTypeId",
            "scope_id"
        ],
        "ls_replication_repl_unit_of_measure"            => [
            "nav_id",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_vendor"                     => [
            "Name",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_hierarchy_hosp_deal_line"   => [
            "DealNo",
            "ItemNo",
            "LineNo",
            "UnitOfMeasure",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_hierarchy_hosp_deal"        => [
            "DealNo",
            "No",
            "LineNo",
            "UnitOfMeasure",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_item_recipe"                => [
            "ItemNo",
            "RecipeNo",
            "UnitOfMeasure",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_repl_item_modifier"              => [
            "nav_id",
            "VariantCode",
            "Code",
            "SubCode",
            "TriggerCode",
            "UnitOfMeasure",
            "scope_id",
            "processed",
            "is_updated",
            "IsDeleted"
        ],
        "ls_replication_loy_item"                        => [
            "nav_id"
        ]
    ];

    /** @var array List of Replication Tables with unique column */
    private static $uniqueColumnsArray = [
        "ls_replication_repl_attribute"                  => ["Code", "scope_id"],
        "ls_replication_repl_attribute_option_value"     => ["Code", "Sequence", "scope_id"],
        "ls_replication_repl_attribute_value"            => [
            "Code",
            "LinkField1",
            "LinkField2",
            "LinkField3",
            "Sequence",
            "scope_id"
        ],
        "ls_replication_repl_barcode"                    => ["nav_id", "scope_id"],
        "ls_replication_repl_country_code"               => ["Name", "scope_id"],
        "ls_replication_repl_currency"                   => ["CurrencyCode", "scope_id"],
        "ls_replication_repl_currency_exch_rate"         => ["CurrencyCode", "scope_id"],
        "ls_replication_repl_customer"                   => ["AccountNumber", "scope_id"],
        "ls_replication_repl_data_translation"           => ["TranslationId", "Key", "LanguageCode", "scope_id"],
        "ls_replication_repl_data_translation_lang_code" => ["Code", "scope_id"],
        "ls_replication_repl_discount"                   => [
            "ItemId",
            "LoyaltySchemeCode",
            "OfferNo",
            "StoreId",
            "VariantId",
            "MinimumQuantity",
            "scope_id"
        ],
        "ls_replication_repl_discount_validation"        => ["nav_id", "scope_id"],
        "ls_replication_repl_extended_variant_value"     => [
            "Code",
            "FrameworkCode",
            "ItemId",
            "Value",
            "scope_id"
        ],
        "ls_replication_repl_hierarchy"                  => ["nav_id", "scope_id"],
        "ls_replication_repl_hierarchy_leaf"             => ["nav_id", "NodeId", "scope_id"],
        "ls_replication_repl_hierarchy_node"             => ["nav_id", "scope_id"],
        "ls_replication_repl_image"                      => ["nav_id", "scope_id"],
        "ls_replication_repl_image_link"                 => ["ImageId", "KeyValue", "scope_id"],
        "ls_replication_repl_item"                       => ["nav_id", "scope_id"],
        "ls_replication_repl_item_category"              => ["nav_id", "scope_id"],
        "ls_replication_repl_item_unit_of_measure"       => ["Code", "ItemId", "scope_id"],
        "ls_replication_repl_item_variant_registration"  => [
            "ItemId",
            "VariantId",
            "scope_id"
        ],
        "ls_replication_repl_loy_vendor_item_mapping"    => ["NavManufacturerId", "NavProductId", "scope_id"],
        "ls_replication_repl_price"                      => [
            "ItemId",
            "VariantId",
            "StoreId",
            "QtyPerUnitOfMeasure",
            "UnitOfMeasure",
            "scope_id"
        ],
        "ls_replication_repl_inv_status"                 => ["ItemId", "VariantId", "StoreId", "scope_id"],
        "ls_replication_repl_product_group"              => ["nav_id", "scope_id"],
        "ls_replication_repl_shipping_agent"             => ["Name", "scope_id"],
        "ls_replication_repl_store"                      => ["nav_id", "scope_id"],
        "ls_replication_repl_store_tender_type"          => ["TenderTypeId", "scope_id"],
        "ls_replication_repl_unit_of_measure"            => ["nav_id", "scope_id"],
        "ls_replication_repl_vendor"                     => ["Name", "scope_id"],
        "ls_replication_repl_hierarchy_hosp_deal_line"   => [
            "DealNo",
            "ItemNo",
            "LineNo",
            "UnitOfMeasure",
            "scope_id"
        ],
        "ls_replication_repl_hierarchy_hosp_deal"        => ["DealNo", "No", "LineNo", "UnitOfMeasure", "scope_id"],
        "ls_replication_repl_item_recipe"                => ["ItemNo", "RecipeNo", "UnitOfMeasure", "scope_id"],
        "ls_replication_repl_item_modifier"              => [
            "nav_id",
            "VariantCode",
            "Code",
            "SubCode",
            "UnitOfMeasure",
            "scope_id"
        ],
        "ls_replication_repl_tax_setup"                  => ["BusinessTaxGroup", "ProductTaxGroup", "scope_id"]
    ];
    /** @var Metadata */
    protected $metadata;

    /**
     * SchemaUpdateGenerator constructor.
     * @param Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Create dynamic db_schema.xml file and save to etc folder of Replication Module
     * @throws ReflectionException
     */
    public function generate()
    {
        $dom               = new DOMDocument('1.0');
        $dom->formatOutput = true;
        $schema            = $dom->createElement('schema');
        $schema->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $schema->setAttribute('xsi:noNamespaceSchemaLocation', 'urn:magento:framework:Setup/Declaration/Schema/etc/schema.xsd');
        $tables = [];
        foreach ($this->metadata->getOperations() as $operationName => $operation) {
            if (strpos($operationName, 'ReplEcomm') !== false) {
                $replicationOperation = $this->metadata->getReplicationOperationByName($operation->getName());
                $tableName            = "ls_replication_" . $replicationOperation->getTableName();
                $tableIncludedInIndex = (array_key_exists($tableName, self::$indexerColumnLists) ? true : false);
                if (!in_array($tableName, $tables)) {
                    $table = $dom->createElement('table');
                    $table->setAttribute('name', $tableName);
                    $table->setAttribute('resource', 'default');
                    $table->setAttribute('engine', 'innodb');
                    $table->setAttribute('comment', $replicationOperation->getName());
                    $column = $dom->createElement('column');
                    $column->setAttribute('xsi:type', 'int');
                    $column->setAttribute('name', $replicationOperation->getTableColumnId());
                    $column->setAttribute('padding', '10');
                    $column->setAttribute('unsigned', 'false');
                    $column->setAttribute('nullable', 'false');
                    $column->setAttribute('identity', 'true');
                    $column->setAttribute('comment', $replicationOperation->getTableColumnId());
                    $table->appendChild($column);
                    $extraColumnsArray   = [
                        [
                            'name'       => 'processed',
                            'field_type' => 'boolean',
                            'default'    => '0',
                            'comment'    => 'Flag to check if data is already copied into Magento. 0 means needs to be copied into Magento tables & 1 means already copied'
                        ],
                        [
                            'name'       => 'is_updated',
                            'field_type' => 'boolean',
                            'default'    => '0',
                            'comment'    => 'Flag to check if data is already updated from Omni into Magento. 0 means already updated & 1 means needs to be updated into Magento tables'
                        ],
                        [
                            'name'       => 'is_failed',
                            'field_type' => 'boolean',
                            'default'    => '0',
                            'comment'    => 'Flag to check if data is already added from Flat into Magento successfully or not. 0 means already added successfully & 1 means failed to add successfully into Magento tables'
                        ],
                        [
                            'name'       => 'checksum',
                            'field_type' => 'text',
                            'default'    => '',
                            'comment'    => 'Checksum'
                        ],
                        [
                            'name'       => 'processed_at',
                            'field_type' => 'timestamp',
                            'default'    => '',
                            'comment'    => 'Processed At'
                        ],
                        [
                            'name'       => 'created_at',
                            'field_type' => 'timestamp',
                            'default'    => 'CURRENT_TIMESTAMP',
                            'comment'    => 'Created At'
                        ],
                        [
                            'name'       => 'updated_at',
                            'field_type' => 'timestamp',
                            'default'    => 'CURRENT_TIMESTAMP',
                            'comment'    => 'Updated At'
                        ]
                    ];
                    $restrictions        = $this->metadata->getRestrictions();
                    $reflectedEntity     = new ClassReflection($replicationOperation->getOmniEntityFqn());
                    $defaultColumnsArray = $propertyTypes = [];
                    $simpleTypes         = ['boolean', 'string', 'int', 'float'];
                    foreach ($reflectedEntity->getProperties() as $property) {
                        $docblock = $property->getDocBlock()->getContents();
                        preg_match('/property\s(:?\w+)\s\$(:?\w+)/m', $docblock, $matches);
                        $type = $matches[1];
                        $name = $matches[2];
                        if (array_search($type, $simpleTypes) === false) {
                            if (array_key_exists($type, $restrictions)) {
                                $propertyTypes[$name] = $type;
                            }
                        } else {
                            $propertyTypes[$name] = $type;
                        }
                    }
                    foreach ($propertyTypes as $raw_name => $type) {
                        $name    = $raw_name;
                        $length  = null;
                        $default = '';

                        (array_search($type, $simpleTypes) === false) and ($type = 'string');
                        if ($type == 'int') {
                            $fieldType = 'int';
                        } elseif ($type == 'float') {
                            $fieldType = 'decimal';
                        } elseif ($type == 'boolean') {
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
                        if ($name == 'Id') {
                            $name = 'nav_id';
                        }

                        /** OMNI-5424, all indexer columns should be varchar 100 */
                        if ($tableIncludedInIndex) {
                            if (in_array($name, self::$indexerColumnLists[$tableName]) && $fieldType == 'text') {
                                $fieldType = 'varchar';
                            }
                        }

                        $defaultColumnsArray[] = [
                            'name'       => $name,
                            'field_type' => $fieldType,
                            'default'    => $default,
                            'comment'    => $name
                        ];
                    }
                    $allColumnsArray = array_merge($defaultColumnsArray, $extraColumnsArray);
                    foreach ($allColumnsArray as $columnValue) {
                        $extraColumn = $dom->createElement('column');
                        $extraColumn->setAttribute('xsi:type', $columnValue['field_type']);
                        $extraColumn->setAttribute('name', $columnValue['name']);
                        if ($columnValue['field_type'] == 'decimal') {
                            $extraColumn->setAttribute('scale', '4');
                            $extraColumn->setAttribute('precision', '20');
                        }
                        if ($columnValue['field_type'] == 'int')
                            $extraColumn->setAttribute('padding', '11');
                        if ($columnValue['field_type'] == 'varchar')
                            $extraColumn->setAttribute('length', 200);
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
                    $column->setAttribute('name', $replicationOperation->getTableColumnId());
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
                                $referenceId     = strtoupper(implode("_", array($tableName, $indexerColumn)));
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
                    if (array_key_exists($tableName, self::$uniqueColumnsArray)) {
                        $uniqueColumns = self::$uniqueColumnsArray[$tableName];
                        if ($uniqueColumns && !empty($uniqueColumns)) {
                            $uniqueColumnNode = $dom->createElement('constraint');
                            $uniqueColumnNode->setAttribute('xsi:type', 'unique');
                            $fields      = implode('_', array_values($uniqueColumns));
                            $prefix      = 'unq_';
                            $referenceId = strtoupper(ExpressionConverter::shortenEntityName(
                                $tableName . '_' . $fields,
                                $prefix
                            ));
                            $uniqueColumnNode->setAttribute('referenceId', $referenceId);
                            foreach ($uniqueColumns as $uniqueColumn) {
                                $column = $dom->createElement('column');
                                $column->setAttribute('name', $uniqueColumn);
                                $uniqueColumnNode->appendChild($column);
                            }
                        }
                        $table->appendChild($uniqueColumnNode);
                    }

                    $schema->appendChild($table);
                    array_push($tables, $tableName);
                }
            }
        }

        $dom->appendChild($schema);
        $dom->save($this->getPath());
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var  Reader $dirReader */
        $dirReader = $objectManager->get('\Magento\Framework\Module\Dir\Reader');
        $basePath  = $dirReader->getModuleDir('', 'Ls_Replication');
        return $basePath . "/etc/db_schema.xml";
    }
}
