<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class Price implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $table_name = $setup->getTable( 'lsr_replication_price' ); 
        if ( ! $setup->tableExists( $table_name ) ) {

        	$table = $setup->getConnection()->newTable( $table_name );
        	//$table = new Table();
        	//$table->setName( $table_name ); 

        	$table->addColumn( 'price_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'primary' => TRUE,
        	                      'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment'=> TRUE ] );
        	$table->addColumn( 'Amount' , Table::TYPE_TEXT );
        	$table->addColumn( 'Amt' , Table::TYPE_FLOAT );
        	$table->addColumn( 'ItemId' , Table::TYPE_TEXT );
        	$table->addColumn( 'UomId' , Table::TYPE_TEXT );
        	$table->addColumn( 'VariantId' , Table::TYPE_TEXT );
        	$table->addColumn( 'CurrencyCode' , Table::TYPE_TEXT );
        	$table->addColumn( 'CustomerDiscountGroup' , Table::TYPE_TEXT );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'LoyaltySchemeCode' , Table::TYPE_TEXT );
        	$table->addColumn( 'StoreId' , Table::TYPE_TEXT );
        	$table->addColumn( 'UOMId' , Table::TYPE_TEXT );
        	$table->addColumn( 'UnitPrice' , Table::TYPE_FLOAT );
        	$table->addColumn( 'UnitPriceInclVAT' , Table::TYPE_FLOAT );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

