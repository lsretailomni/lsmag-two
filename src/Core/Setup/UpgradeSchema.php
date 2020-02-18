<?php

namespace Ls\Core\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 * @package Ls\Core\Setup
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
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addDocumentIdInSalesOrderGrid($setup);
        }
        $setup->endSetup();
    }

    /***
     * @param SchemaSetupInterface $setup
     */
    public function addDocumentIdInSalesOrderGrid(SchemaSetupInterface $setup)
    {
        $salesOrderTable     = $setup->getTable('sales_order');
        $salesOrderGridTable = $setup->getTable('sales_order_grid');
        $connection          = $setup->getConnection();
        $connection->addColumn(
            $salesOrderGridTable,
            'document_id',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'Document Id'
            ]
        );
        if ($connection->tableColumnExists($salesOrderGridTable, 'document_id') === true) {
            $connection->query(
                $connection->updateFromSelect(
                    $connection->select()
                        ->join(
                            $salesOrderTable,
                            sprintf('%s.entity_id = %s.entity_id', $salesOrderGridTable, $salesOrderTable),
                            'document_id'
                        ),
                    $salesOrderGridTable
                )
            );
        }
    }
}
