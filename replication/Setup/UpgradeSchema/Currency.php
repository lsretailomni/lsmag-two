<?php

namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class Currency implements UpgradeSchemaBlockInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $table_name = $setup->getTable( 'lsr_replication_currency' ); 
        if ( ! $setup->tableExists( $table_name ) ) {

        	$table = $setup->getConnection()->newTable( $table_name );
        	//$table = new Table();
        	//$table->setName( $table_name ); 

        	$table->addColumn( 'currency_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'primary' => TRUE,
        	                      'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment'=> TRUE ] );
        	$table->addColumn( 'AmountRoundingMethod' , Table::TYPE_TEXT );
        	$table->addColumn( 'Culture' , Table::TYPE_TEXT );
        	$table->addColumn( 'DecimalPlaces' , Table::TYPE_INTEGER );
        	$table->addColumn( 'DecimalSeparator' , Table::TYPE_TEXT );
        	$table->addColumn( 'Description' , Table::TYPE_TEXT );
        	$table->addColumn( 'Id' , Table::TYPE_TEXT );
        	$table->addColumn( 'Postfix' , Table::TYPE_TEXT );
        	$table->addColumn( 'Prefix' , Table::TYPE_TEXT );
        	$table->addColumn( 'RoundOfAmount' , Table::TYPE_FLOAT );
        	$table->addColumn( 'RoundOffSales' , Table::TYPE_FLOAT );
        	$table->addColumn( 'SaleRoundingMethod' , Table::TYPE_TEXT );
        	$table->addColumn( 'Symbol' , Table::TYPE_TEXT );
        	$table->addColumn( 'ThousandSeparator' , Table::TYPE_TEXT );
        	$table->addColumn( 'CurrencyCode' , Table::TYPE_TEXT );
        	$table->addColumn( 'CurrencyPrefix' , Table::TYPE_TEXT );
        	$table->addColumn( 'CurrencySuffix' , Table::TYPE_TEXT );
        	$table->addColumn( 'Del' , Table::TYPE_BOOLEAN );
        	$table->addColumn( 'RoundOfSales' , Table::TYPE_FLOAT );
        	$table->addColumn( 'RoundOfTypeAmount' , Table::TYPE_INTEGER );
        	$table->addColumn( 'RoundOfTypeSales' , Table::TYPE_INTEGER );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

