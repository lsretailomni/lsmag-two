<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Lstables for truncating all flat tables
 */
class Lstables extends Action
{
    /** @var Logger */
    public $logger;

    /** @var ResourceConnection */
    public $resource;

    public const TABLE_CONFIGS = [
        'ls_mag/replication/',
        'ls_mag/replication/last_execute_',
        'ls_mag/replication/status_',
        'ls_mag/replication/max_key_'
    ];

    /**
     * @param ResourceConnection $resource
     * @param Logger $logger
     * @param Context $context
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $logger,
        Context $context
    ) {
        $this->resource = $resource;
        $this->logger   = $logger;
        parent::__construct($context);
    }

    /**
     * Truncate ls_ Tables
     *
     * @return void
     */
    public function execute()
    {
        $connection          = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $arguments           = [];
        $jobName             = $this->_request->getParam('jobname');
        $storeId             = $this->_request->getParam('store');
        $coreConfigTableName = $this->resource->getTableName('core_config_data');
        $connection->startSetup();

        if ($jobName != '' && $storeId != '') {
            $replicationTableName = 'ls_replication_' . $jobName;
            $replicationTableName = $this->resource->getTableName($replicationTableName);

            try {
                $connection->delete($replicationTableName, ['scope_id = ?' => $storeId]);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }

            foreach (self::TABLE_CONFIGS as $config) {
                $connection->delete(
                    $coreConfigTableName,
                    ['path = ?' => $config . $jobName, 'scope_id = ?' => $storeId]
                );
            }
            $message      = __('%1 table truncated successfully.', $jobName);
            $redirectPath = 'ls_repl/cron/grid/';
            $arguments    = ['store' => $storeId];
        } else {
            foreach (LSR::LS_TABLES as $lsTables) {
                $tableName = $this->resource->getTableName($lsTables);

                try {
                    $connection->truncateTable($tableName);
                } catch (Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
            $connection->delete($coreConfigTableName, ['path like ?' => 'ls_mag/replication/%']);
            $message      = __('All ls_ tables truncated successfully.');
            $redirectPath = 'adminhtml/system_config/edit/section/ls_mag';
        }

        $connection->endSetup();
        $this->messageManager->addSuccessMessage($message);
        $this->_redirect($redirectPath, $arguments);
    }
}
