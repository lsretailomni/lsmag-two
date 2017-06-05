<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class ItemVariantRegistration implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $table_name = $setup->getTable( 'lsr_replication_item_variant_registration' ); 
        if ( ! $setup->tableExists( $table_name ) ) {

        	$table = $setup->getConnection()->newTable( $table_name );
        	//$table = new Table();
        	//$table->setName( $table_name ); 

        	$table->addColumn( 'item_variant_registration_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'primary' => TRUE,
        	                      'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment'=> TRUE ] );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'FrameworkCode' , Table::TYPE_TEXT );
        	$table->addColumn( 'ItemId' , Table::TYPE_TEXT );
        	$table->addColumn( 'VarDim1' , Table::TYPE_TEXT );
        	$table->addColumn( 'VarDim2' , Table::TYPE_TEXT );
        	$table->addColumn( 'VarDim3' , Table::TYPE_TEXT );
        	$table->addColumn( 'VarDim4' , Table::TYPE_TEXT );
        	$table->addColumn( 'VarDim5' , Table::TYPE_TEXT );
        	$table->addColumn( 'VarDim6' , Table::TYPE_TEXT );
        	$table->addColumn( 'VariantId' , Table::TYPE_TEXT );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

