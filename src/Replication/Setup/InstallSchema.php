<?php

namespace Ls\Replication\Setup;

use \Ls\Replication\Setup\UpgradeSchema\AbstractUpgradeSchema;
use \Ls\Replication\Setup\UpgradeSchema\UpgradeSchemaBlockInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Code\Reflection\ClassReflection;

/**
 * Class InstallSchema
 * @package Ls\Replication\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /** @var  SchemaSetupInterface */
    private $installer;
    /** @var  ModuleContextInterface */
    private $context;

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $this->installer = $setup;
        $this->context = $context;

        $this->installer->startSetup();
        // @codingStandardsIgnoreStart
        $fs = new Filesystem();
        $anchor = new ClassReflection(AbstractUpgradeSchema::class);
        $base_namespace = $anchor->getNamespaceName();
        $filename = $anchor->getFileName();
        $folder = dirname($filename);
        $upgrades = glob($folder . DIRECTORY_SEPARATOR . '*');
        foreach ($upgrades as $upgrade_file) {
            if (strpos($upgrade_file, 'AbstractUpgradeSchema') === false) {
                if ($fs->exists($upgrade_file)) {
                    $upgrade_class = str_replace('.php', '', $fs->makePathRelative($upgrade_file, $folder));
                    $upgrade_class_fqn = $base_namespace . '\\' . substr($upgrade_class, 0, -1);
                    /** @var AbstractUpgradeSchema $upgrade */
                    $upgrade = new $upgrade_class_fqn();
                    $upgrade->upgrade($this->installer, $this->context);
                }
            }
        }
        // @codingStandardsIgnoreEnd
        $this->installer->endSetup();
    }
}
