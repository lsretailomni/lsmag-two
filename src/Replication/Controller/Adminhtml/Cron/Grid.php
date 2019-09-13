<?php

namespace Ls\Replication\Controller\Adminhtml\Cron;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use \Ls\Core\Model\LSR;

/**
 * Class Grid
 * @package Ls\Replication\Controller\Adminhtml\Cron
 */
class Grid extends Action
{
    /** Url path */
    const URL_PATH_EXECUTE = 'ls_repl/cron/grid';

    /** @var PageFactory */
    public $resultPageFactory;

    /** @var ObjectManagerInterface */
    public $objectManager;

    /** @var LoggerInterface */
    public $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * Grid constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     * @param StoreManager $storeManager
     * @param LSR $lsr
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        StoreManager $storeManager,
        LSR $lsr
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->lsr = $lsr;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        try {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Cron Listing '));
            $jobUrl = $this->_request->getParam('joburl');
            $jobName = $this->_request->getParam('jobname');
            $storeId = $this->_request->getParam('store');
            $storeData = null;
            if ($jobUrl != "") {
                // @codingStandardsIgnoreStart
                $cron = $this->objectManager->create($jobUrl);
                // @codingStandardsIgnoreEnd
                if (!empty($storeId)) {
                    $storeData=$this->storeManager->getStore($storeId);
                }
                $info = $cron->executeManually($storeData);
                if (!empty($info)) {
                    $executeMoreData = '';
                    if ($info[0] > 0) {
                        $executeMoreData = $this->_url->getUrl(
                            self::URL_PATH_EXECUTE,
                            ['joburl' => $jobUrl, 'jobname' => $jobName,'store' => $storeId]
                        );
                    }
                    $this->messageManager->addComplexSuccessMessage(
                        'cronlinkmessage',
                        ['url' => $executeMoreData, 'jobName' => $jobName, 'remaining' => $info[0]]
                    );
                }
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                return $resultRedirect;
            } else {
                if (empty($storeId)) {
                    $storeId=$this->lsr->getStoreConfig(LSR::SC_REPLICATION_MANUAL_CRON_GRID_DEFAULT_STORE);
                    $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    $resultRedirect->setPath(self::URL_PATH_EXECUTE.'/store/'.$storeId);
                    return $resultRedirect;
                }
                    return $resultPage;
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
