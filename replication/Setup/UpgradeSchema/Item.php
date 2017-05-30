<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class Item implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if ( ! $setup->tableExists( 'lsr_replication_item' ) ) {

        	$table = new Table();
        	$table->setName( 'lsr_replication_item' ); 

        	$table->addColumn( 'item_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment' => TRUE ] );
        	$table->addColumn( 'AllowedToSell' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'Description' , Table::TYPE_BLOB );
        	$table->addColumn( 'Details' , Table::TYPE_BLOB );
        	$table->addColumn( 'Id' , Table::TYPE_BLOB );
        	$table->addColumn( 'Price' , Table::TYPE_FLOAT );
        	$table->addColumn( 'ProductGroupId' , Table::TYPE_BLOB );
        	$table->addColumn( 'SalesUomId' , Table::TYPE_BLOB );
        	$table->addColumn( 'BaseUOM' , Table::TYPE_BLOB );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'FullDescription' , Table::TYPE_BLOB );
        	$table->addColumn( 'ProductGroupCode' , Table::TYPE_BLOB );
        	$table->addColumn( 'PurchUOM' , Table::TYPE_BLOB );
        	$table->addColumn( 'SalesUOM' , Table::TYPE_BLOB );
        	$table->addColumn( 'ScaleItem' , Table::TYPE_INTEGER );
        	$table->addColumn( 'VendorId' , Table::TYPE_BLOB );
        	$table->addColumn( 'VendorItemId' , Table::TYPE_BLOB );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

