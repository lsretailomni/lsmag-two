<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class AttributeOptionValue implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if ( ! $setup->tableExists( 'lsr_replication_attribute_option_value' ) ) {

        	$table = new Table();
        	$table->setName( 'lsr_replication_attribute_option_value' ); 

        	$table->addColumn( 'attribute_option_value_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment' => TRUE ] );
        	$table->addColumn( 'Code' , Table::TYPE_BLOB );
        	$table->addColumn( 'Sequence' , Table::TYPE_INTEGER );
        	$table->addColumn( 'Value' , Table::TYPE_BLOB );
        	$table->addColumn( 'IsDeleted' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

