<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Class Attribute Deletion
 */
class Attribute extends Action
{
    /** @var LoggerInterface */
    public $logger;

    /** @var ResourceConnection */
    public $resource;

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
     * Order Deletion constructor.
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger,
        Context $context
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
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
        $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        $tableName = $connection->getTableName('eav_attribute');
        $query = "DELETE FROM " . $tableName ." WHERE attribute_code LIKE 'ls\_%'";
        try {
            $connection->query($query);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        // Update all dependent ls tables to not processed
        foreach ($this->ls_tables as $lsTable) {
            $lsTableName = $connection->getTableName($lsTable);
            $lsQuery = "UPDATE " . $lsTableName . " SET processed = 0;";
            try {
                $connection->query($lsQuery);
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
        // @codingStandardsIgnoreEnd
        $this->messageManager->addSuccessMessage(__('LS Attributes deleted successfully.'));
        $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
