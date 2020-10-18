<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category as ResourceModelCategory;
use Magento\Store\Api\Data\StoreInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CategoryDeletion
 */
class Category extends Action
{

    /**
     * @var Logger
     */
    public $logger;

    /** @var Registry $registry */
    public $registry;

    /** @var ResourceConnection */
    public $resource;

    /** @var LSR */
    public $lsr;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var Filesystem */
    public $filesystem;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;
    /**
     * @var ResourceModelCategory
     */
    protected $categoryResourceModel;

    // @codingStandardsIgnoreStart
    /** @var array */
    protected $_publicActions = ['category'];
    // @codingStandardsIgnoreEnd

    /** @var array List of ls tables required in categories */
    public $lsTables = [
        "ls_replication_repl_hierarchy_node",
        "ls_replication_repl_hierarchy_leaf"
    ];


    /** @var array List of magento tables required in categories */
    public $categoryTables = [
        "catalog_category_product",
        "catalog_category_product_index",
        "catalog_category_product_index_tmp"
    ];

    /**
     * Category constructor.
     * @param Logger $logger
     * @param Context $context
     * @param ResourceConnection $resource
     * @param LSR $LSR
     * @param ReplicationHelper $replicationHelper
     * @param Filesystem $filesystem
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ResourceModelCategory $categoryResourceModel
     */
    public function __construct(
        Logger $logger,
        Context $context,
        ResourceConnection $resource,
        LSR $LSR,
        ReplicationHelper $replicationHelper,
        Filesystem $filesystem,
        CategoryRepositoryInterface $categoryRepository,
        ResourceModelCategory $categoryResourceModel
    ) {
        $this->logger                = $logger;
        $this->resource              = $resource;
        $this->lsr                   = $LSR;
        $this->replicationHelper     = $replicationHelper;
        $this->filesystem            = $filesystem;
        $this->categoryRepository    = $categoryRepository;
        $this->categoryResourceModel = $categoryResourceModel;

        parent::__construct($context);
    }

    /**
     * Remove categories tree
     *
     * @return void
     */
    public function execute()
    {
        try {
            /** @var StoreInterface[] $stores */
            $stores = $this->lsr->getAllStores();
            if (!empty($stores)) {
                foreach ($stores as $store) {
                    $rootCategory = $this->categoryRepository->get($store->getRootCategoryId());
                    $this->categoryResourceModel->deleteChildren($rootCategory);
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }

        // @codingStandardsIgnoreStart
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);

        $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        foreach ($this->categoryTables as $categoryTable) {
            $tableName = $this->resource->getTableName($categoryTable);
            try {
                if ($connection->isTableExists($tableName)) {
                    $connection->truncateTable($tableName);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        // Update dependent ls tables to not processed
        foreach ($this->lsTables as $lsTable) {
            $lsTableName = $this->resource->getTableName($lsTable);
            // @codingStandardsIgnoreLine
            $lsQuery = 'UPDATE ' . $lsTableName . ' SET processed = 0, is_updated = 0, is_failed = 0, processed_at = NULL;';
            try {
                $connection->query($lsQuery);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $mediaDirectory = $this->replicationHelper->getMediaPathtoStore();
        $mediaDirectory .= 'catalog' . DIRECTORY_SEPARATOR . 'category' . DIRECTORY_SEPARATOR;
        try {
            if ($this->filesystem->exists($mediaDirectory)) {
                $this->filesystem->remove($mediaDirectory);
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        // remove the url keys from url_rewrite table.
        $this->replicationHelper->resetUrlRewriteByType('category');

        $this->replicationHelper->updateCronStatusForAllStores(
            false,
            LSR::SC_SUCCESS_CRON_CATEGORY
        );
        $this->messageManager->addSuccessMessage(__('Categories deleted successfully.'));
        $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
