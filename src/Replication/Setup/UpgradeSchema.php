<?php

namespace Ls\Replication\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /** @var string[] */
    public static $versions = [ ];
    /**
     * @var  SchemaSetupInterface
     */
    private $installer = null;
    /**
     * @var  ModuleContextInterface
     */
    private $context = null;

    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installer = $setup;
        $this->context = $context;

        $this->installer->startSetup();

        foreach (UpgradeSchema::$versions as $version) {
            if (version_compare($version, $this->context->getVersion()) == -1) {
                $safe_version = UpgradeSchema::sanitizeVersion($version);
                $method_name = "upgrade$safe_version";
                $this->{$method_name}();
            }
        }

        $this->installer->endSetup();
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public static function sanitizeVersion($version)
    {
        return str_replace('.', '_', $version);
    }
}
