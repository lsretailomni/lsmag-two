<?php
namespace Ls\Replication\Setup\UpgradeSchema;


use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

abstract class AbstractUpgradeSchema
{
    abstract function upgrade ( SchemaSetupInterface $setup, ModuleContextInterface $context );
}
