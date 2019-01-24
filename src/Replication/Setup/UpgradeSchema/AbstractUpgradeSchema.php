<?php
namespace Ls\Replication\Setup\UpgradeSchema;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class AbstractUpgradeSchema
 * @package Ls\Replication\Setup\UpgradeSchema
 */
abstract class AbstractUpgradeSchema
{
    abstract public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context);
}
