<?php

namespace Ls\Replication\Setup\Patch\Data;

use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Data patch to update all repl_items
 */
class ResetAllCronsData implements DataPatchInterface
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
        $this->moduleDataSetup = $moduleDataSetup;
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
        $this->resetAllCronsData();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Reset all crons data
     *
     * @return void
     */
    public function resetAllCronsData()
    {
        $coreConfigTableName = $this->replicationHelper->getGivenTableName('core_config_data');
        $this->replicationHelper->deleteGivenTableDataGivenConditions(
            $coreConfigTableName,
            ['path like ?' => 'ls_mag/replication/%']
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
