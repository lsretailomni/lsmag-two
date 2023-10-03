<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Model\ResourceModel\ReplAttribute\CollectionFactory as ReplAttributeCollectionFactory;
use \Ls\Replication\Model\ResourceModel\ReplExtendedVariantValue\CollectionFactory as ReplExtendedCollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

abstract class AbstractReset extends Action
{
    public const LS_ITEM_RELATED_TABLES = [
        'ls_replication_repl_item',
        'ls_replication_repl_item_variant',
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

    public const LS_TRANSLATION_TABLE = 'ls_replication_repl_data_translation';

    /** @var ReplicationHelper */
    public $replicationHelper;

    /**
     * @var ReplAttributeCollectionFactory
     */
    public $replAttributeCollectionFactory;

    /**
     * @var ReplExtendedCollectionFactory
     */
    public $replExtendedCollectionFactory;

    /**
     * @param Context $context
     * @param ReplicationHelper $replicationHelper
     * @param ReplAttributeCollectionFactory $replAttributeCollectionFactory
     * @param ReplExtendedCollectionFactory $replExtendedCollectionFactory
     */
    public function __construct(
        Context $context,
        ReplicationHelper $replicationHelper,
        ReplAttributeCollectionFactory $replAttributeCollectionFactory,
        ReplExtendedCollectionFactory $replExtendedCollectionFactory
    ) {
        parent::__construct($context);
        $this->replicationHelper              = $replicationHelper;
        $this->replAttributeCollectionFactory = $replAttributeCollectionFactory;
        $this->replExtendedCollectionFactory  = $replExtendedCollectionFactory;
    }

    /**
     * Truncate all given tables
     *
     * @param $tables
     * @return void
     */
    public function truncateAllGivenTables($tables)
    {
        $connection = $this->replicationHelper->getConnection();
        $connection->startSetup();

        foreach ($tables as $table) {
            $tableName = $this->replicationHelper->getGivenTableName($table);
            $this->replicationHelper->truncateGivenTable($tableName);
        }
        $connection->endSetup();
    }

    /**
     * Update All dependent ls tables
     *
     * @param $tables
     * @param $where
     * @return void
     */
    public function updateAllGivenTablesToUnprocessed($tables, $where)
    {
        foreach ($tables as $table) {
            $lsTableName = $this->replicationHelper->getGivenTableName($table);
            $this->replicationHelper->updateGivenTableDataGivenConditions(
                $lsTableName,
                [
                    'processed'    => 0,
                    'is_updated'   => 0,
                    'is_failed'    => 0,
                    'processed_at' => null
                ],
                $where
            );
        }
    }

    /**
     * Update data translation tables
     *
     * @param $where
     * @return void
     */
    public function updateDataTranslationTables($where)
    {
        $lsTableName = $this->replicationHelper->getGivenTableName(
            self::LS_TRANSLATION_TABLE
        );
        $this->replicationHelper->updateGivenTableDataGivenConditions(
            $lsTableName,
            [
                'processed'    => 0,
                'is_updated'   => 0,
                'is_failed'    => 0,
                'processed_at' => null
            ],
            $where
        );
    }
}
