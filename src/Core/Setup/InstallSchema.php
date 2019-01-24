<?php

namespace Ls\Core\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package Ls\Core\Setup
 */
class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;

        $installer->startSetup();

        /*
         * add column `user` to `cron_schedule`
         */
        $installer->getConnection()->addColumn($installer->getTable('cron_schedule'), 'parameters', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => 100,
            'nullable' => true,
            'comment' => 'Accept parameters from the specific job types'
        ]);

        /**
         * For Click and Collect.
         *
         */
        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'pickup_date',
            [
                'type' => 'datetime',
                'nullable' => true,
                'comment' => 'Pick Up Date',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'pickup_store',
            [
                'type' => 'text',
                'nullable' => true,
                'comment' => 'Pick Up Store',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'pickup_date',
            [
                'type' => 'datetime',
                'nullable' => true,
                'comment' => 'Pick Up Date',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'pickup_store',
            [
                'type' => 'text',
                'nullable' => true,
                'comment' => 'Pick Up Store',
            ]
        );

        $installer->endSetup();
    }
}
