<?php
declare(strict_types=1);

namespace Ls\Replication\Controller\Adminhtml\Reset;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use \Ls\Replication\Model\ResourceModel\ReplInvStatus\CollectionFactory;
use Psr\Log\LoggerInterface;

class MassResetInventory extends Action
{
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
        public Filter $filter,
        public PageFactory $resultPageFactory,
        public CollectionFactory $collectionFactory,
        public ResourceConnection $resource,
        public LoggerInterface $logger
    ) {
        parent::__construct($context);
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
            foreach ($collection as $item) {
                $item->setProcessed(0)
                    ->setIsFailed(0)
                    ->setIsUpdated(0)
                    ->setProcessedAt(null)
                    ->save();
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
