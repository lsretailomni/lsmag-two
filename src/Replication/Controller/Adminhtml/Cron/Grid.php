<?php

namespace Ls\Replication\Controller\Adminhtml\Cron;

use Exception;
use Ls\Core\Model\LSR;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Grid
 * @package Ls\Replication\Controller\Adminhtml\Cron
 */
class Grid extends Action
{
    /** @var PageFactory */
    public $resultPageFactory;

    /** @var ObjectManagerInterface */
    public $objectManager;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var StoreManagerInterface
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
     * @param Logger $logger
     * @param StoreManager $storeManager
     * @param LSR $lsr
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ObjectManagerInterface $objectManager,
        Logger $logger,
        StoreManager $storeManager,
        LSR $lsr
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->objectManager     = $objectManager;
        $this->logger            = $logger;
        $this->storeManager      = $storeManager;
        $this->lsr               = $lsr;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        try {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Cron Listing '));
            $jobUrl    = $this->_request->getParam('joburl');
            $jobName   = $this->_request->getParam('jobname');
            $storeId   = $this->_request->getParam('store');
            $storeData = null;
            if ($jobUrl != "") {
                // @codingStandardsIgnoreStart
                $cron = $this->objectManager->create($jobUrl);
                // @codingStandardsIgnoreEnd
                if (!empty($storeId)) {
                    $storeData = $this->storeManager->getStore($storeId);
                }
                $info = $cron->executeManually($storeData);
                if (!empty($info)) {
                    $executeMoreData = '';
                    if ($info[0] > 0) {
                        $executeMoreData = $this->_url->getUrl(
                            LSR::URL_PATH_EXECUTE,
                            ['joburl' => $jobUrl, 'jobname' => $jobName, 'store' => $storeId]
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
                    $storeId        = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_MANUAL_CRON_GRID_DEFAULT_STORE);
                    $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    $resultRedirect->setPath(LSR::URL_PATH_EXECUTE . '/store/' . $storeId);
                    return $resultRedirect;
                }
                return $resultPage;
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
