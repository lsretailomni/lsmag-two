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
    private $installer;
    private $context;

    /**
     * {@inheritdoc}
     */
    public function install ( SchemaSetupInterface $setup, ModuleContextInterface $context ) {

        $this->installer = $setup;
        $this->context = $context;

        $this->installer->startSetup();

//        $this->createTables();

        $this->installer->endSetup();
    }
}
