<?php

namespace Ls\Replication\Setup\Patch\Schema;

use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Schema patch to truncate all flat tables
 */
class TruncateAllFlatTables implements SchemaPatchInterface
{
    public const LS_ITEM_RELATED_TABLES = [
        'ls_replication_repl_item',
        'ls_replication_repl_item_variant_registration',
        'ls_replication_repl_price',
        'ls_replication_repl_barcode',
        'ls_replication_repl_inv_status',
        'ls_replication_repl_hierarchy_leaf',
        'ls_replication_repl_attribute_value',
        'ls_replication_repl_image_link',
        'ls_replication_repl_item_unit_of_measure',
        'ls_replication_repl_loy_vendor_item_mapping',
        'ls_replication_repl_item_modifier',
        'ls_replication_repl_item_recipe',
        'ls_replication_repl_hierarchy_hosp_deal',
        'ls_replication_repl_hierarchy_hosp_deal_line'
    ];

    /** List of ls tables required in attributes */
    public const LS_ATTRIBUTE_RELATED_TABLES = [
        'ls_replication_repl_attribute',
        'ls_replication_repl_attribute_option_value',
        'ls_replication_repl_extended_variant_value'
    ];

    /** List of ls tables required in categories */
    public const LS_CATEGORY_RELATED_TABLES = [
        'ls_replication_repl_hierarchy_node',
        'ls_replication_repl_hierarchy_leaf'
    ];

    /** List of ls tables required in tax rules */
    public const LS_TAX_RELATED_TABLES = [
        'ls_replication_repl_country_code',
        'ls_replication_repl_tax_setup'
    ];

    /** List of ls tables required in discount rules */
    public const LS_DISCOUNT_RELATED_TABLES = [
        'ls_replication_repl_discount',
        'ls_replication_repl_discount_validation'
    ];

    public const LS_TRANSLATION_TABLES = [
        'ls_replication_repl_data_translation',
        'ls_replication_repl_data_translation_lang_code'
    ];

    /** List of all the ls_ tables */
    public const LS_TABLES = [
        'ls_replication_loy_item',
        'ls_replication_repl_currency',
        'ls_replication_repl_currency_exch_rate',
        'ls_replication_repl_customer',
        'ls_replication_repl_hierarchy',
        'ls_replication_repl_image',
        'ls_replication_repl_item_category',
        'ls_replication_repl_product_group',
        'ls_replication_repl_shipping_agent',
        'ls_replication_repl_store',
        'ls_replication_repl_store_tender_type',
        'ls_replication_repl_unit_of_measure',
        'ls_replication_repl_vendor'
    ];

    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /**
     * @param SchemaSetupInterface $schemaSetup
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        ReplicationHelper $replicationHelper
    ) {
        $this->schemaSetup = $schemaSetup;
        $this->replicationHelper = $replicationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        $this->truncateAllFlatTables();
        $this->schemaSetup->endSetup();
    }

    /**
     * Truncate all flat tables
     *
     * @return void
     */
    public function truncateAllFlatTables()
    {
        $mergedTables = array_merge(
            self::LS_DISCOUNT_RELATED_TABLES,
            self::LS_TAX_RELATED_TABLES,
            self::LS_ATTRIBUTE_RELATED_TABLES,
            self::LS_CATEGORY_RELATED_TABLES,
            self::LS_ITEM_RELATED_TABLES,
            self::LS_TABLES,
            self::LS_TRANSLATION_TABLES
        );
        foreach ($mergedTables as $lsTable) {
            $tableName = $this->replicationHelper->getGivenTableName($lsTable);
            $this->replicationHelper->truncateGivenTable($tableName);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
