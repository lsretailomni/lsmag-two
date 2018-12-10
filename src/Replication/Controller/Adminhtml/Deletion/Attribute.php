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
    protected $logger;

    /** @var ResourceConnection */
    protected $_resource;

    /** @var array */
    protected $_publicActions = ['attribute'];

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
        $this->_resource = $resource;
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
        $connection = $this->_resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        $tableName = $connection->getTableName('eav_attribute');
        $query = "DELETE FROM " . $tableName ." WHERE attribute_code LIKE 'ls_%'";
        try {
            $connection->query($query);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
        $this->messageManager->addSuccessMessage(__('LS Attributes deleted successfully.'));
        $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
