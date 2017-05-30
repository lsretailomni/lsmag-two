<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class Store implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if ( ! $setup->tableExists( 'lsr_replication_store' ) ) {

        	$table = new Table();
        	$table->setName( 'lsr_replication_store' ); 

        	$table->addColumn( 'store_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment' => TRUE ] );
        	$table->addColumn( 'CurrencyCode' , Table::TYPE_BLOB );
        	$table->addColumn( 'Description' , Table::TYPE_BLOB );
        	$table->addColumn( 'Distance' , Table::TYPE_FLOAT );
        	$table->addColumn( 'Id' , Table::TYPE_BLOB );
        	$table->addColumn( 'IsClickAndCollect' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'Latitude' , Table::TYPE_FLOAT );
        	$table->addColumn( 'Longitude' , Table::TYPE_FLOAT );
        	$table->addColumn( 'Phone' , Table::TYPE_BLOB );
        	$table->addColumn( 'CAC' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'City' , Table::TYPE_BLOB );
        	$table->addColumn( 'Country' , Table::TYPE_BLOB );
        	$table->addColumn( 'County' , Table::TYPE_BLOB );
        	$table->addColumn( 'CultureName' , Table::TYPE_BLOB );
        	$table->addColumn( 'Currency' , Table::TYPE_BLOB );
        	$table->addColumn( 'DefaultCustAcct' , Table::TYPE_BLOB );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'FunctProfile' , Table::TYPE_BLOB );
        	$table->addColumn( 'Name' , Table::TYPE_BLOB );
        	$table->addColumn( 'State' , Table::TYPE_BLOB );
        	$table->addColumn( 'Street' , Table::TYPE_BLOB );
        	$table->addColumn( 'TaxGroup' , Table::TYPE_BLOB );
        	$table->addColumn( 'UserDefaultCustAcct' , Table::TYPE_INTEGER );
        	$table->addColumn( 'ZipCode' , Table::TYPE_BLOB );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

