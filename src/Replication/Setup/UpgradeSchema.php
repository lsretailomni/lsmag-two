<?php

namespace Ls\Replication\Setup;

use \Ls\Replication\Cron\ReplEcommAttributeValueTask;
use \Ls\Replication\Cron\ReplEcommDiscountsTask;
use \Ls\Replication\Cron\ReplEcommInventoryStatusTask;
use \Ls\Replication\Cron\ReplEcommItemsTask;
use \Ls\Replication\Cron\ReplEcommPricesTask;
use \Ls\Replication\Cron\ReplEcommStoresTask;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Setup\UpgradeSchema\AbstractUpgradeSchema;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Code\Reflection\ClassReflection;

/**
 * Class UpgradeSchema
 * @package Ls\Replication\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /** @var ReplicationHelper */
    public $replicationHelper;

    /**
     * UpgradeSchema constructor.
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        ReplicationHelper $replicationHelper
    ) {
        $this->replicationHelper = $replicationHelper;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \ReflectionException
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        // @codingStandardsIgnoreStart
        $fs             = new Filesystem();
        $anchor         = new ClassReflection(AbstractUpgradeSchema::class);
        $base_namespace = $anchor->getNamespaceName();
        $filename       = $anchor->getFileName();
        $folder         = dirname($filename);
        $upgrades       = glob($folder . DIRECTORY_SEPARATOR . '*');
        foreach ($upgrades as $upgrade_file) {
            if (strpos($upgrade_file, 'AbstractUpgradeSchema') === false) {
                if ($fs->exists($upgrade_file)) {
                    $upgrade_class     = str_replace('.php', '', $fs->makePathRelative($upgrade_file, $folder));
                    $upgrade_class_fqn = $base_namespace . '\\' . substr($upgrade_class, 0, -1);
                    /** @var AbstractUpgradeSchema $upgrade */
                    $upgrade = new $upgrade_class_fqn();
                    $upgrade->upgrade($setup, $context);
                }
            }
        }
        if (version_compare($context->getVersion(), '1.2.1', '<')) {
            $this->replicationHelper->updateCronStatus(false, ReplEcommItemsTask::CONFIG_PATH_STATUS);
            $this->replicationHelper->updateCronStatus(false, ReplEcommItemsTask::CONFIG_PATH);
            $this->replicationHelper->updateCronStatus(false, ReplEcommInventoryStatusTask::CONFIG_PATH_STATUS);
            $this->replicationHelper->updateCronStatus(false, ReplEcommInventoryStatusTask::CONFIG_PATH);
            $this->replicationHelper->updateCronStatus(false, ReplEcommStoresTask::CONFIG_PATH_STATUS);
            $this->replicationHelper->updateCronStatus(false, ReplEcommStoresTask::CONFIG_PATH);
            $this->replicationHelper->updateCronStatus(false, ReplEcommAttributeValueTask::CONFIG_PATH_STATUS);
            $this->replicationHelper->updateCronStatus(false, ReplEcommAttributeValueTask::CONFIG_PATH);
            $this->replicationHelper->updateCronStatus(false, ReplEcommDiscountsTask::CONFIG_PATH_STATUS);
            $this->replicationHelper->updateCronStatus(false, ReplEcommDiscountsTask::CONFIG_PATH);
            $this->replicationHelper->updateCronStatus(false, ReplEcommPricesTask::CONFIG_PATH_STATUS);
            $this->replicationHelper->updateCronStatus(false, ReplEcommPricesTask::CONFIG_PATH);
        }
        if (version_compare($context->getVersion(), '1.2.2', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable(Gallery::GALLERY_TABLE), 'image_id', [
                    'type'     => Table::TYPE_TEXT,
                    'nullable' => true,
                    'default'  => null,
                    'comment'  => 'LS Central Image Id'
                ]
            );
        }
        // @codingStandardsIgnoreEnd
        $setup->endSetup();
    }
}
