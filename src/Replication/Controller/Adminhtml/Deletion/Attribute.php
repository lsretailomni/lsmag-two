<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Attribute Deletion
 */
class Attribute extends Action
{
    /**
     * @var Logger
     */
    public $logger;

    /** @var ResourceConnection */
    public $resource;

    /** @var LSR */
    public $lsr;

    /** @var ReplicationHelper */
    public $replicationHelper;

    // @codingStandardsIgnoreStart
    /** @var array */
    protected $_publicActions = ['attribute'];
    // @codingStandardsIgnoreEnd

    /** @var array List of ls tables required in attributes */
    public $ls_tables = [
        "ls_replication_repl_attribute",
        "ls_replication_repl_attribute_option_value",
        "ls_replication_repl_extended_variant_value"
    ];

    /**
     * Attribute constructor.
     * @param ResourceConnection $resource
     * @param Logger $logger
     * @param Context $context
     * @param LSR $LSR
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $logger,
        Context $context,
        LSR $LSR,
        ReplicationHelper $replicationHelper
    ) {
        $this->resource          = $resource;
        $this->logger            = $logger;
        $this->lsr               = $LSR;
        $this->replicationHelper = $replicationHelper;
        parent::__construct($context);
    }

    /**
     * Remove Attributes
     *
     * @return void
     */
    public function execute()
    {
        // @codingStandardsIgnoreStart
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $tableName  = $connection->getTableName('eav_attribute');
        $query      = "DELETE FROM $tableName WHERE attribute_code LIKE 'ls\_%'";
        try {
            $connection->query($query);
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        // Update all dependent ls tables to not processed
        foreach ($this->ls_tables as $lsTable) {
            $lsTableName = $connection->getTableName($lsTable);
            $lsQuery     = 'UPDATE ' . $lsTableName . ' SET processed = 0;';
            try {
                $connection->query($lsQuery);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $this->replicationHelper->updateCronStatus(
            false,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE
        );
        $this->replicationHelper->updateCronStatus(
            false,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT
        );
        // @codingStandardsIgnoreEnd
        $this->messageManager->addSuccessMessage(__('LS Attributes deleted successfully.'));
        $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
