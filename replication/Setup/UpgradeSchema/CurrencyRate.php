<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class CurrencyRate implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $table_name = $setup->getTable( 'lsr_replication_currency_rate' ); 
        if ( ! $setup->tableExists( $table_name ) ) {

        	$table = $setup->getConnection()->newTable( $table_name );

        	$table->addColumn( 'currency_rate_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'primary' => TRUE,
        	                      'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment'=> TRUE ] );
        	$table->addColumn( 'CC' , Table::TYPE_TEXT );
        	$table->addColumn( 'CF' , Table::TYPE_FLOAT );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'SD' , Table::TYPE_TEXT );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

