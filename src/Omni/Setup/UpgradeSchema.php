<?php

namespace Ls\Omni\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 * @package Ls\Omni\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $columnsUpdateType = [
            ['table' => 'quote', 'column' => 'ls_points_earn'],
            ['table' => 'quote', 'column' => 'ls_points_spent'],
            ['table' => 'sales_order', 'column' => 'ls_points_earn'],
            ['table' => 'sales_order', 'column' => 'ls_points_spent'],
            ['table' => 'sales_invoice', 'column' => 'ls_points_earn'],
            ['table' => 'sales_invoice', 'column' => 'ls_points_spent'],
            ['table' => 'sales_creditmemo', 'column' => 'ls_points_earn'],
            ['table' => 'sales_creditmemo', 'column' => 'ls_points_spent']
        ];
        foreach ($columnsUpdateType as $item) {
            $setup->getConnection()->addColumn(
                $setup->getTable($item['table']),
                $item['column'],
                [
                    'type' => Table::TYPE_INTEGER,
                    'visible' => false,
                    'nullable' => true,
                    'default' => '0',
                    'comment' => 'LS Loyalty Points'
                ]
            );
        }

        $columnsUpdateType = [
            ['table' => 'quote', 'column' => 'ls_gift_card_no','datatype' => Table::TYPE_TEXT,'default'=>''],
            ['table' => 'quote', 'column' => 'ls_gift_card_amount_used','datatype' => Table::TYPE_FLOAT,'default'=>0],
            ['table' => 'sales_order', 'column' => 'ls_gift_card_no','datatype' => Table::TYPE_TEXT,'default'=>''],
            ['table' => 'sales_order', 'column' => 'ls_gift_card_amount_used','datatype' => Table::TYPE_FLOAT,
                'default'=>0],
            ['table' => 'sales_invoice', 'column' => 'ls_gift_card_no','datatype' => Table::TYPE_TEXT,'default'=>''],
            ['table' => 'sales_invoice', 'column' => 'ls_gift_card_amount_used','datatype' => Table::TYPE_FLOAT,
                'default'=>0],
            ['table' => 'sales_creditmemo', 'column' => 'ls_gift_card_no','datatype' => Table::TYPE_TEXT,'default'=>''],
            ['table' => 'sales_creditmemo', 'column' => 'ls_gift_card_amount_used','datatype' => Table::TYPE_FLOAT,
                'default'=>0]
        ];
        foreach ($columnsUpdateType as $item) {
            $setup->getConnection()->addColumn(
                $setup->getTable($item['table']),
                $item['column'],
                [
                    'type' =>  $item['datatype'],
                    'visible' => false,
                    'nullable' => true,
                    'default' =>  $item['default'],
                    'comment' => 'Ls Gift Card'
                ]
            );
        }

        $setup->endSetup();
    }

}