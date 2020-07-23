<?php

namespace Ls\Core\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

/**
 * Class AddColumnsInCoreTables
 * @package Ls\Core\Setup\Patch\Schema
 */
class AddColumnsInCoreTables implements SchemaPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * AddCronScheduleQuoteSalesOrderColumns constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '1.0.1';
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $this->addQuoteColumns();
        $this->addSalesOrderColumns();
        $this->addSalesInvoiceColumns();
        $this->addSalesCreditMemoColumns();
        $this->addSalesOrderGridColumns();
        $this->addCronScheduleColumns();
        $this->moduleDataSetup->endSetup();
    }

    /**
     * addQuoteColumns
     */
    private function addQuoteColumns()
    {
        $columns = [
            ['table' => 'quote', 'column' => 'pickup_date', 'type' => Table::TYPE_DATETIME, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'Pick Up Date'],
            ['table' => 'quote', 'column' => 'pickup_store', 'type' => Table::TYPE_TEXT, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'Pick Up Store'],
            ['table' => 'quote', 'column' => 'ls_points_earn', 'type' => Table::TYPE_INTEGER, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'LS Loyalty Points Earned'],
            ['table' => 'quote', 'column' => 'ls_points_spent', 'type' => Table::TYPE_INTEGER, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'LS Loyalty Points Spent'],
            ['table' => 'quote', 'column' => 'ls_gift_card_no', 'type' => Table::TYPE_TEXT, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'LS Gift Card No'],
            ['table' => 'quote', 'column' => 'ls_gift_card_amount_used', 'type' => Table::TYPE_FLOAT, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'Ls Gift Card Amount Used']
        ];
        $this->addColumnsHelper($columns);
    }

    /**
     * @param $columns
     * addColumnsHelper
     */
    private function addColumnsHelper($columns)
    {
        $moduleDataSetup = $this->moduleDataSetup;
        foreach ($columns as $item) {
            $moduleDataSetup->getConnection()->addColumn(
                $moduleDataSetup->getTable($item['table']),
                $item['column'],
                [
                    'type'     => $item['type'],
                    'visible'  => $item['visible'],
                    'nullable' => $item['nullable'],
                    'default'  => $item['default'],
                    'comment'  => $item['comment']
                ]
            );
        }
    }

    /**
     * addSalesOrderColumn
     */
    private function addSalesOrderColumns()
    {
        $columns = [
            ['table' => 'sales_order', 'column' => 'pickup_date', 'type' => Table::TYPE_DATETIME, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'Pick Up Date'],
            ['table' => 'sales_order', 'column' => 'pickup_store', 'type' => Table::TYPE_TEXT, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'Pick Up Store'],
            ['table' => 'sales_order', 'column' => 'ls_points_earn', 'type' => Table::TYPE_INTEGER, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'LS Loyalty Points Earned'],
            ['table' => 'sales_order', 'column' => 'ls_points_spent', 'type' => Table::TYPE_INTEGER, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'LS Loyalty Points Spent'],
            ['table' => 'sales_order', 'column' => 'ls_gift_card_no', 'type' => Table::TYPE_TEXT, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'LS Gift Card No'],
            ['table' => 'sales_order', 'column' => 'ls_gift_card_amount_used', 'type' => Table::TYPE_FLOAT, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'Ls Gift Card Amount Used'],
            ['table' => 'sales_order', 'column' => 'document_id', 'type' => Table::TYPE_TEXT, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'Document Id']
        ];
        $this->addColumnsHelper($columns);
    }

    /**
     * addSalesInvoiceColumns
     */
    private function addSalesInvoiceColumns()
    {
        $columns = [
            ['table' => 'sales_invoice', 'column' => 'ls_points_earn', 'type' => Table::TYPE_INTEGER, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'LS Loyalty Points Earned'],
            ['table' => 'sales_invoice', 'column' => 'ls_points_spent', 'type' => Table::TYPE_INTEGER, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'LS Loyalty Points Spent'],
            ['table' => 'sales_invoice', 'column' => 'ls_gift_card_no', 'type' => Table::TYPE_TEXT, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'LS Gift Card No'],
            ['table' => 'sales_invoice', 'column' => 'ls_gift_card_amount_used', 'type' => Table::TYPE_FLOAT, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'Ls Gift Card Amount Used'],
        ];
        $this->addColumnsHelper($columns);
    }

    /**
     * addSalesCreditMemoColumns
     */
    private function addSalesCreditMemoColumns()
    {
        $columns = [
            ['table' => 'sales_creditmemo', 'column' => 'ls_points_earn', 'type' => Table::TYPE_INTEGER, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'LS Loyalty Points Earned'],
            ['table' => 'sales_creditmemo', 'column' => 'ls_points_spent', 'type' => Table::TYPE_INTEGER, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'LS Loyalty Points Spent'],
            ['table' => 'sales_creditmemo', 'column' => 'ls_gift_card_no', 'type' => Table::TYPE_TEXT, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'LS Gift Card No'],
            ['table' => 'sales_creditmemo', 'column' => 'ls_gift_card_amount_used', 'type' => Table::TYPE_FLOAT, 'visible' => false, 'nullable' => true, 'default' => '0', 'comment' => 'Ls Gift Card Amount Used'],
        ];
        $this->addColumnsHelper($columns);
    }

    /**
     * addSalesOrderGridColumns
     */
    private function addSalesOrderGridColumns()
    {
        $columns = [['table' => 'sales_order_grid', 'column' => 'document_id', 'type' => Table::TYPE_TEXT, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'Document Id']];
        $this->addColumnsHelper($columns);
    }

    /**
     * addCronScheduleColumns
     */
    private function addCronScheduleColumns()
    {
        $columns = [['table' => 'cron_schedule', 'column' => 'parameters', 'type' => Table::TYPE_TEXT, 'visible' => false, 'nullable' => true, 'default' => NULL, 'comment' => 'Accept parameters from the specific job types']];
        $this->addColumnsHelper($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
