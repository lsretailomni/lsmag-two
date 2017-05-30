<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class Vendor implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if ( ! $setup->tableExists( 'lsr_replication_vendor' ) ) {

        	$table = new Table();
        	$table->setName( 'lsr_replication_vendor' ); 

        	$table->addColumn( 'vendor_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment' => TRUE ] );
        	$table->addColumn( 'ACTSPS' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'COUtc' , Table::TYPE_BLOB );
        	$table->addColumn( 'DO' , Table::TYPE_INTEGER );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'Deleted' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'Id' , Table::TYPE_BLOB );
        	$table->addColumn( 'MTId' , Table::TYPE_INTEGER );
        	$table->addColumn( 'Name' , Table::TYPE_BLOB );
        	$table->addColumn( 'PId' , Table::TYPE_INTEGER );
        	$table->addColumn( 'PS' , Table::TYPE_INTEGER );
        	$table->addColumn( 'PSO' , Table::TYPE_BLOB );
        	$table->addColumn( 'Pub' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'UOUtc' , Table::TYPE_BLOB );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

