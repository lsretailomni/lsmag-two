<?php

namespace Ls\Replication\Setup\Patch\Data;

use Exception;
use \Ls\Replication\Logger\Logger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Data patch to update all repl_items
 */
class UpdateReplItemsStatus implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var Logger
     */
    private $logger;

    /** @var ResourceConnection */
    private $resource;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ResourceConnection $resource
     * @param Logger $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConnection $resource,
        Logger $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resource        = $resource;
        $this->logger          = $logger;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->updateReplItemsTable();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Update all repl_item records
     */
    private function updateReplItemsTable()
    {
        $connection   = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $lsTableName  = $this->resource->getTableName('ls_replication_repl_item');
        try {
            $connection->update($lsTableName, ['is_updated' => 1]);
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
