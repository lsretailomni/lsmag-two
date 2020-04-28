<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
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

    /** @var CategoryFactory $categoryFactory */
    public $categoryFactory;

    /** @var ResourceConnection */
    public $resource;

    /** @var LSR */
    public $lsr;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var Filesystem */
    public $filesystem;

    // @codingStandardsIgnoreStart
    /** @var array */
    protected $_publicActions = ['category'];
    // @codingStandardsIgnoreEnd

    /**
     * Category constructor.
     * @param CategoryFactory $categoryFactory
     * @param Registry $registry
     * @param Logger $logger
     * @param Context $context
     * @param ResourceConnection $resource
     * @param LSR $LSR
     * @param ReplicationHelper $replicationHelper
     * @param Filesystem $filesystem
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        Registry $registry,
        Logger $logger,
        Context $context,
        ResourceConnection $resource,
        LSR $LSR,
        ReplicationHelper $replicationHelper,
        Filesystem $filesystem
    ) {
        $this->categoryFactory   = $categoryFactory;
        $this->registry          = $registry;
        $this->logger            = $logger;
        $this->resource          = $resource;
        $this->lsr               = $LSR;
        $this->replicationHelper = $replicationHelper;
        $this->filesystem        = $filesystem;
        parent::__construct($context);
    }

    /**
     * Remove categories tree
     *
     * @return void
     */
    public function execute()
    {
        $categories = $this->categoryFactory->create()->getCollection();
        $this->registry->register("isSecureArea", true);
        foreach ($categories as $category) {
            if ($category->getId() > 2) {
                try {
                    // @codingStandardsIgnoreStart
                    $category->delete();
                    // @codingStandardsIgnoreEnd
                } catch (Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
        }
        $connection  = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $lsTableName = $connection->getTableName('ls_replication_repl_hierarchy_node');
        $lsQuery     = 'UPDATE ' . $lsTableName . ' SET processed = 0, is_updated = 0, is_failed = 0, processed_at = NULL;';
        try {
            $connection->query($lsQuery);
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
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
