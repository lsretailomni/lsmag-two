<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Class Customer Deletion
 */
class Customer extends Action
{
    /** @var LoggerInterface */
    public $logger;

    /** @var ResourceConnection */
    public $resource;

    /** @var array List of all the Customer tables */
    public $customer_tables = [
        "customer_address_entity",
        "customer_address_entity_datetime",
        "customer_address_entity_decimal",
        "customer_address_entity_int",
        "customer_address_entity_text",
        "customer_address_entity_varchar",
        "customer_entity",
        "customer_entity_datetime",
        "customer_entity_decimal",
        "customer_entity_int",
        "customer_entity_text",
        "customer_entity_varchar",
        "customer_grid_flat",
        "customer_log",
        "customer_log",
        "customer_visitor",
        "persistent_session",
        "wishlist",
        "wishlist_item",
        "wishlist_item_option",
    ];

    // @codingStandardsIgnoreStart
    /** @var array  */
    protected $_publicActions = ['customer'];
    // @codingStandardsIgnoreEnd

    /**
     * Product Deletion constructor.
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
     * Remove customers
     *
     * @return void
     */
    public function execute()
    {
        // @codingStandardsIgnoreStart
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        foreach ($this->customer_tables as $customerTable) {
            $tableName = $connection->getTableName($customerTable);
            $query = "TRUNCATE TABLE " . $tableName;
            try {
                $connection->query($query);
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
        // @codingStandardsIgnoreEnd
        $this->messageManager->addSuccessMessage(__('Customers deleted successfully.'));
        $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
