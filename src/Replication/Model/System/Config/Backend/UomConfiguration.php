<?php

namespace Ls\Replication\Model\System\Config\Backend;

use \Ls\Replication\Api\ReplItemUnitOfMeasureRepositoryInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Uom Configuration backend model
 */
class UomConfiguration extends Value
{
    /**
     * @var ReplItemUnitOfMeasureRepositoryInterface
     */
    private $replItemUomRepository;

    /**
     * @var ReplicationHelper
     */
    private $replicationHelper;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ReplItemUnitOfMeasureRepositoryInterface $replItemUomRepository
     * @param ReplicationHelper $replicationHelper
     * @param LoggerInterface $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ReplItemUnitOfMeasureRepositoryInterface $replItemUomRepository,
        ReplicationHelper $replicationHelper,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->replItemUomRepository = $replItemUomRepository;
        $this->replicationHelper     = $replicationHelper;
        $this->resourceConnection    = $resourceConnection;
        $this->logger                = $logger;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Mark all UOM records as updated after save
     *
     * @return Value
     */
    public function afterSave()
    {
        if ($this->isValueChanged()) {
            $this->markAllUomRecordsAsUpdated();
        }

        return parent::afterSave();
    }

    /**
     * Mark all UOM records as updated
     *
     * @return void
     */
    private function markAllUomRecordsAsUpdated()
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName  = $connection->getTableName('ls_replication_repl_item_unit_of_measure');

            $scope   = $this->getScope();
            $scopeId = $this->getScopeId();

            $whereConditions = ['is_updated = ?' => 0];

            if ($scope === 'websites' && $scopeId) {
                $whereConditions['scope_id = ?'] = $scopeId;
            }

            $affectedRows = $connection->update(
                $tableName,
                ['is_updated' => 1],
                $whereConditions
            );

            $scopeInfo = $scope === 'websites' ? " for website ID {$scopeId}" : " (default scope)";
            $this->logger->info("UOM configuration changed{$scopeInfo}. Updated {$affectedRows} records.");
        } catch (\Exception $e) {
            $this->logger->error("Error updating UOM records: " . $e->getMessage());
        }
    }
}
