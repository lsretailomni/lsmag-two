<?php

namespace Ls\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class AddDataInSalesOrderGrid
 * @package Ls\Core\Setup\Patch\Data
 */
class AddDataInSalesOrderGrid implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * AddDataInSalesOrderGrid constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
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
        return '1.0.1';
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->addDataInSalesOrderGrid();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * addDataInSalesOrderGrid
     */
    private function addDataInSalesOrderGrid()
    {
        $connection       = $this->moduleDataSetup->getConnection();
        $sourceTable      = $this->moduleDataSetup->getTable('sales_order');
        $destinationTable = $this->moduleDataSetup->getTable('sales_order_grid');
        if ($connection->tableColumnExists($this->moduleDataSetup->getTable('sales_order_grid'), 'document_id') === true) {
            $connection->query(
                $connection->updateFromSelect(
                    $connection->select()
                        ->join(
                            $sourceTable,
                            sprintf('%s.entity_id = %s.entity_id', $destinationTable, $sourceTable),
                            'document_id'
                        ),
                    $destinationTable
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
