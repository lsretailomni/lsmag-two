<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class Vendor implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $table_name = $setup->getTable( 'lsr_replication_vendor' ); 
        if ( ! $setup->tableExists( $table_name ) ) {

        	$table = $setup->getConnection()->newTable( $table_name );

        	$table->addColumn( 'vendor_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'primary' => TRUE,
        	                      'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment'=> TRUE ] );
        	$table->addColumn( 'ACTSPS' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'COUtc' , Table::TYPE_TEXT );
        	$table->addColumn( 'DO' , Table::TYPE_INTEGER );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'Deleted' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'Id' , Table::TYPE_TEXT );
        	$table->addColumn( 'MTId' , Table::TYPE_INTEGER );
        	$table->addColumn( 'Name' , Table::TYPE_TEXT );
        	$table->addColumn( 'PId' , Table::TYPE_INTEGER );
        	$table->addColumn( 'PS' , Table::TYPE_INTEGER );
        	$table->addColumn( 'PSO' , Table::TYPE_TEXT );
        	$table->addColumn( 'Pub' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'UOUtc' , Table::TYPE_TEXT );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

