<?php

namespace Ls\Replication\Setup\Patch\Data;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ReplEcommPricesTask;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Data patch to reset repl_price cron data and configurations
 */
class ResetPriceCronData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ReplicationHelper
     */
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
        $this->resetPriceCronData();
        $this->resetPriceTableProcessedStatus();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Reset price cron data
     *
     * @return void
     */
    public function resetPriceCronData()
    {
        $coreConfigTableName = $this->replicationHelper->getGivenTableName('core_config_data');
        $this->replicationHelper->deleteGivenTableDataGivenConditions(
            $coreConfigTableName,
            [
                'path IN (?)' => [
                    ReplEcommPricesTask::CONFIG_PATH,
                    ReplEcommPricesTask::CONFIG_PATH_STATUS,
                    ReplEcommPricesTask::CONFIG_PATH_LAST_EXECUTE,
                    ReplEcommPricesTask::CONFIG_PATH_MAX_KEY,
                    ReplEcommPricesTask::CONFIG_PATH_APP_ID,
                    LSR::SC_SUCCESS_CRON_PRODUCT_PRICE,
                    LSR::SC_PRODUCT_PRICE_CONFIG_PATH_LAST_EXECUTE
                ]
            ]
        );
    }

    /**
     * Clear all records from repl_price table
     * This forces a complete fresh sync of all price data from LS Central
     * Uses DELETE instead of TRUNCATE to work within transaction
     *
     * @return void
     */
    public function resetPriceTableProcessedStatus()
    {
        $replPriceTableName = $this->replicationHelper->getGivenTableName('ls_replication_repl_price');
        $connection = $this->moduleDataSetup->getConnection();
        $connection->delete($replPriceTableName);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
