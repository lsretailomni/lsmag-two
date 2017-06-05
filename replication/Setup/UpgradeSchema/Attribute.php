<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class Attribute implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $table_name = $setup->getTable( 'lsr_replication_attribute' ); 
        if ( ! $setup->tableExists( $table_name ) ) {

        	$table = $setup->getConnection()->newTable( $table_name );
        	//$table = new Table();
        	//$table->setName( $table_name ); 

        	$table->addColumn( 'attribute_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'primary' => TRUE,
        	                      'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment'=> TRUE ] );
        	$table->addColumn( 'Code' , Table::TYPE_TEXT );
        	$table->addColumn( 'DefaultValue' , Table::TYPE_TEXT );
        	$table->addColumn( 'Description' , Table::TYPE_TEXT );
        	$table->addColumn( 'LinkField1' , Table::TYPE_TEXT );
        	$table->addColumn( 'LinkField2' , Table::TYPE_TEXT );
        	$table->addColumn( 'LinkField3' , Table::TYPE_TEXT );
        	$table->addColumn( 'LinkType' , Table::TYPE_TEXT );
        	$table->addColumn( 'NumbericValue' , Table::TYPE_FLOAT );
        	$table->addColumn( 'Sequence' , Table::TYPE_INTEGER );
        	$table->addColumn( 'Value' , Table::TYPE_TEXT );
        	$table->addColumn( 'ValueType' , Table::TYPE_INTEGER );
        	$table->addColumn( 'IsDeleted' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

