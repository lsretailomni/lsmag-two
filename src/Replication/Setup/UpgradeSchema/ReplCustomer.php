<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class ReplCustomer
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $table_name = $setup->getTable( 'ls_replication_repl_customer' ); 
        if ( ! $setup->tableExists( $table_name ) ) {

        	$table = $setup->getConnection()->newTable( $table_name );

        	$table->addColumn( 'repl_customer_id', Table::TYPE_INTEGER, NULL, 
        	                    [ 'identity' => TRUE, 'primary' => TRUE,
        	                      'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment'=> TRUE ] );
        	$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
        	$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
        	$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => 0 ],'flag to check if data is already coped into magento 0 means needs to be copied into Magento tables, 1 means already copied' );
        	$table->addColumn( 'is_updated', Table::TYPE_BOOLEAN, null, [ 'default' => 0 ],'flag to check if data is already updated from Omni into magento 0 means already updated, 1 means  needs to be updated into Magento tables' );
        	$table->addColumn( 'AccountNumber' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'Blocked' , Table::TYPE_INTEGER, '' );
        	$table->addColumn( 'CardId' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'CellularPhone' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'City' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'ClubCode' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'Country' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'County' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'Currency' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'Email' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'FirstName' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'nav_id' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'IncludeTax' , Table::TYPE_INTEGER, '' );
        	$table->addColumn( 'IsDeleted' , Table::TYPE_BOOLEAN, '' );
        	$table->addColumn( 'LastName' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'MiddleName' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'Name' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'NamePrefix' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'NameSuffix' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'PhoneLocal' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'ReceiptEmail' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'ReceiptOption' , Table::TYPE_INTEGER, '' );
        	$table->addColumn( 'SchemeCode' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'State' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'Street' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'TaxGroup' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'URL' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'UserName' , Table::TYPE_TEXT, '' );
        	$table->addColumn( 'ZipCode' , Table::TYPE_TEXT, '' );

        	$setup->getConnection()->createTable( $table );
        }
    }


}

