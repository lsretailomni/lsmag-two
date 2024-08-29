<?php

namespace Ls\Replication\Setup\Patch\Data;

use Ls\Replication\Cron\ReplEcommItemsTask;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Data patch to update repl_items
 */
class ResetItemsCronData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ReplicationHelper $replicationHelper
    ) {
        $this->moduleDataSetup   = $moduleDataSetup;
        $this->replicationHelper = $replicationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->resetItemsCronData();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Reset items cron data
     *
     * @return void
     */
    public function resetItemsCronData()
    {
        $coreConfigTableName = $this->replicationHelper->getGivenTableName('core_config_data');
        $this->replicationHelper->deleteGivenTableDataGivenConditions(
            $coreConfigTableName,
            [
                'path IN (?)' => [
                    ReplEcommItemsTask::CONFIG_PATH,
                    ReplEcommItemsTask::CONFIG_PATH_STATUS,
                    ReplEcommItemsTask::CONFIG_PATH_LAST_EXECUTE,
                    ReplEcommItemsTask::CONFIG_PATH_MAX_KEY
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
