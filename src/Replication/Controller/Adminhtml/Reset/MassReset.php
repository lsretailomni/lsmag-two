<?php

namespace Ls\Replication\Controller\Adminhtml\Reset;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use \Ls\Replication\Model\ResourceModel\ReplItem\CollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Mass reset controller
 */
class MassReset extends Action
{
    /** @var array List of ls tables required to reset */
    public $lsTables = [
        ['table' => 'ls_replication_repl_item_variant_registration', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_extended_variant_value', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_price', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_barcode', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_inv_status', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_hierarchy_leaf', 'id' => 'nav_id'],
        ['table' => 'ls_replication_repl_attribute_value', 'id' => 'LinkField1'],
        ['table' => 'ls_replication_repl_image_link', 'id' => 'KeyValue'],
        ['table' => 'ls_replication_repl_item_unit_of_measure', 'id' => 'ItemId'],
        ['table' => 'ls_replication_repl_loy_vendor_item_mapping', 'id' => 'NavProductId'],
        ['table' => 'ls_replication_repl_item_modifier', 'id' => 'nav_id'],
        ['table' => 'ls_replication_repl_item_recipe', 'id' => 'RecipeNo'],
        ['table' => 'ls_replication_repl_hierarchy_hosp_deal', 'id' => 'DealNo'],
        ['table' => 'ls_replication_repl_hierarchy_hosp_deal_line', 'id' => 'DealNo'],
    ];

    /**
     * @var Filter
     */
    public $filter;

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @var Collection factory
     */
    public $collectionFactory;

    /** @var ResourceConnection */
    public $resource;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param PageFactory $resultPageFactory
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Filter $filter,
        PageFactory $resultPageFactory,
        CollectionFactory $collectionFactory,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->filter            = $filter;
        $this->resultPageFactory = $resultPageFactory;
        $this->collectionFactory = $collectionFactory;
        $this->resource          = $resource;
        $this->logger            = $logger;
    }

    /**
     * Executing function to unprocessed data
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $updated    = 0;
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
            foreach ($collection as $item) {
                $item->setProcessed(0)
                    ->setIsFailed(0)
                    ->setIsUpdated(0)
                    ->setProcessedAt(null)
                    ->save();
                // Update all dependent ls tables to processed = 0
                foreach ($this->lsTables as $lsTable) {
                    $lsTableName = $this->resource->getTableName($lsTable['table']);
                    $columnName  = $lsTable['id'];
                    $lsQuery     = 'UPDATE ' . $lsTableName .
                        ' SET processed = 0, is_updated = 0, is_failed = 0, processed_at = NULL where ';
                    if ($columnName == 'KeyValue') {
                        $lsQuery .= $columnName . ' like "%' . $item->getNavId() . '%"';
                    } else {
                        $lsQuery .= $columnName . '=\'' . $item->getNavId() . '\'';
                    }
                    try {
                        $connection->query($lsQuery);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
                $updated++;
            }
            if ($updated) {
                $this->messageManager->addComplexSuccessMessage(
                    'resetmessage',
                    ['updated' => $updated]
                );
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
