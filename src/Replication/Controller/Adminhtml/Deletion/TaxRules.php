<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResponseInterface;

/**
 * Controller to delete tax rules
 */
class TaxRules extends Action
{
    /** @var Logger */
    public $logger;

    /** @var ResourceConnection */
    public $resource;

    /** @var LSR */
    public $lsr;

    /** @var array List of all the Magento Tax Rules tables */
    public $tax_rules_tables = [
        "tax_calculation_rule",
        "tax_calculation",
        "tax_calculation_rate",
        "tax_calculation_rate_title"
    ];

    /**
     * @param ResourceConnection $resource
     * @param Logger $logger
     * @param Context $context
     * @param LSR $LSR
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $logger,
        Context $context,
        LSR $LSR
    ) {
        $this->resource = $resource;
        $this->logger   = $logger;
        $this->lsr      = $LSR;
        parent::__construct($context);
    }

    /**
     * Remove Tax Rules
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $connection->startSetup();
        foreach ($this->tax_rules_tables as $taxTable) {
            $tableName = $this->resource->getTableName($taxTable);
            try {
                if ($connection->isTableExists($tableName)) {
                    $connection->truncateTable($tableName);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        $lsTableName  = $this->resource->getTableName('ls_replication_repl_country_code');
        try {
            $connection->update($lsTableName, ['processed' => 0, 'is_updated' => 0, 'is_failed' => 0]);
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }

        $connection->endSetup();
        $this->messageManager->addSuccessMessage(__('Tax Rules deleted successfully.'));

        return $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
