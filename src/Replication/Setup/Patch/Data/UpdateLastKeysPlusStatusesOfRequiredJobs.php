<?php

namespace Ls\Replication\Setup\Patch\Data;

use \Ls\Replication\Cron\ReplEcommAttributeValueTask;
use \Ls\Replication\Cron\ReplEcommDiscountsTask;
use \Ls\Replication\Cron\ReplEcommInventoryStatusTask;
use \Ls\Replication\Cron\ReplEcommItemsTask;
use \Ls\Replication\Cron\ReplEcommPricesTask;
use \Ls\Replication\Cron\ReplEcommStoresTask;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class UpdateLastKeysPlusStatusesOfRequiredJobs
 * @package Ls\Core\Setup\Patch\Data
 */
class UpdateLastKeysPlusStatusesOfRequiredJobs implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /** @var ReplicationHelper */
    private $replicationHelper;

    /**
     * UpdateLastKeysPlusStatusesOfRequiredJobs constructor.
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
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '1.2.1';
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->updateRequiredConfigurations();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * updateRequiredConfigurations
     */
    private function updateRequiredConfigurations()
    {
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

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
