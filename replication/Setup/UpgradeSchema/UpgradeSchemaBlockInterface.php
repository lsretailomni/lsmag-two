<?php
namespace Ls\Replication\Setup\UpgradeSchema;


use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

interface UpgradeSchemaBlockInterface
{
    function upgrade ( SchemaSetupInterface $setup, ModuleContextInterface $context );
}
