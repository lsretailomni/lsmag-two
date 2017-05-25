<?php
namespace Ls\Replication\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /** @var  SchemaSetupInterface */
    private $installer;
    /** @var  ModuleContextInterface */
    private $context;

    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function install ( SchemaSetupInterface $setup, ModuleContextInterface $context ) {

        $this->installer = $setup;
        $this->context = $context;

        $this->installer->startSetup();
        $this->installer->endSetup();
    }
}
