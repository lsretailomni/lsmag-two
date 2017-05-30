<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class Price implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if ( ! $setup->tableExists( 'lsr_replication_price' ) ) {

        	$table = new Table();
        	$table->setName( 'lsr_replication_price' ); 

        	$table->addColumn( 'price_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment' => TRUE ] );
        	$table->addColumn( 'Amount' , Table::TYPE_BLOB );
        	$table->addColumn( 'Amt' , Table::TYPE_FLOAT );
        	$table->addColumn( 'ItemId' , Table::TYPE_BLOB );
        	$table->addColumn( 'UomId' , Table::TYPE_BLOB );
        	$table->addColumn( 'VariantId' , Table::TYPE_BLOB );
        	$table->addColumn( 'CurrencyCode' , Table::TYPE_BLOB );
        	$table->addColumn( 'CustomerDiscountGroup' , Table::TYPE_BLOB );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'LoyaltySchemeCode' , Table::TYPE_BLOB );
        	$table->addColumn( 'StoreId' , Table::TYPE_BLOB );
        	$table->addColumn( 'UOMId' , Table::TYPE_BLOB );
        	$table->addColumn( 'UnitPrice' , Table::TYPE_FLOAT );
        	$table->addColumn( 'UnitPriceInclVAT' , Table::TYPE_FLOAT );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

