<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class Item implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $table_name = $setup->getTable( 'lsr_replication_item' ); 
        if ( ! $setup->tableExists( $table_name ) ) {

        	$table = $setup->getConnection()->newTable( $table_name );

        	$table->addColumn( 'item_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'primary' => TRUE,
        	                      'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment'=> TRUE ] );
        	$table->addColumn( 'AllowedToSell' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'Description' , Table::TYPE_TEXT );
        	$table->addColumn( 'Details' , Table::TYPE_TEXT );
        	$table->addColumn( 'Id' , Table::TYPE_TEXT );
        	$table->addColumn( 'Price' , Table::TYPE_FLOAT );
        	$table->addColumn( 'ProductGroupId' , Table::TYPE_TEXT );
        	$table->addColumn( 'SalesUomId' , Table::TYPE_TEXT );
        	$table->addColumn( 'BaseUOM' , Table::TYPE_TEXT );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'FullDescription' , Table::TYPE_TEXT );
        	$table->addColumn( 'ProductGroupCode' , Table::TYPE_TEXT );
        	$table->addColumn( 'PurchUOM' , Table::TYPE_TEXT );
        	$table->addColumn( 'SalesUOM' , Table::TYPE_TEXT );
        	$table->addColumn( 'ScaleItem' , Table::TYPE_INTEGER );
        	$table->addColumn( 'VendorId' , Table::TYPE_TEXT );
        	$table->addColumn( 'VendorItemId' , Table::TYPE_TEXT );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

