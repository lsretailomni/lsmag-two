<?php

namespace Ls\Replication\Controller\Adminhtml\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\System\Store;

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
     * @var Store
     */
    public $systemStoreManager;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ObjectManagerInterface $objectManager
     * @param Logger $logger
     * @param StoreManager $storeManager
     * @param LSR $lsr
     * @param Store $systemStoreManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ObjectManagerInterface $objectManager,
        Logger $logger,
        StoreManager $storeManager,
        LSR $lsr,
        Store $systemStoreManager
    ) {
        $this->resultPageFactory  = $resultPageFactory;
        $this->objectManager      = $objectManager;
        $this->logger             = $logger;
        $this->storeManager       = $storeManager;
        $this->lsr                = $lsr;
        $this->systemStoreManager = $systemStoreManager;
        parent::__construct($context);
    }

    /**
     * Entry point for the controller
     *
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        try {
            $resultPage = $this->resultPageFactory->create();
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultPage->getConfig()->getTitle()->prepend(__('Cron Listing'));
            $jobUrl    = $this->_request->getParam('joburl');
            $jobName   = $this->_request->getParam('jobname');
            $scopeId   = $this->_request->getParam('scope_id');
            $scope     = $this->_request->getParam('scope');
            $storeId   = $this->_request->getParam('store');
            $websiteId = $this->_request->getParam('website');
            $storeData = null;

            if (empty($scope) && empty($scopeId)) {
                if (!$this->lsr->isSSM()) {
                    $scopeId = $this->getDefaultWebsiteId();
                    $scope = ScopeInterface::SCOPE_WEBSITES;
                    $parameters = [
                        'scope_id' => $scopeId,
                        '_current' => true,
                        'scope' => $scope,
                        'website' => $scopeId
                    ];
                } else {
                    $scopeId = '0';
                    $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                    $parameters = ['scope_id' => $scopeId, '_current' => true, 'scope' => $scope];
                }

                $resultRedirect->setPath(
                    'ls_repl/cron/grid',
                    $parameters
                );

                return $resultRedirect;
            }

            if (!$this->lsr->isSSM()) {
                if ($websiteId !== null &&
                    ($scope != ScopeInterface::SCOPE_WEBSITES || $websiteId != $scopeId)
                ) {
                    $resultRedirect->setPath(
                        'ls_repl/cron/grid',
                        [
                            'scope_id' => $this->_request->getParam('website'),
                            '_current' => true,
                            'scope' => ScopeInterface::SCOPE_WEBSITES
                        ]
                    );

                    return $resultRedirect;
                }

                if ($storeId !== null &&
                    ($scope != ScopeInterface::SCOPE_STORES || $storeId != $scopeId)
                ) {
                    $resultRedirect->setPath(
                        'ls_repl/cron/grid',
                        [
                            'scope_id' => $this->_request->getParam('store'),
                            '_current' => true,
                            'scope' => ScopeInterface::SCOPE_STORES
                        ]
                    );

                    return $resultRedirect;
                }
            }

            if ($jobUrl != "") {
                // @codingStandardsIgnoreStart
                $cron = $this->objectManager->create($jobUrl);
                // @codingStandardsIgnoreEnd
                if (!empty($scopeId) || $scopeId === '0') {
                    $storeData = $scope == ScopeInterface::SCOPE_WEBSITES ?
                        $this->storeManager->getWebsite($scopeId) :
                        $this->storeManager->getStore($scopeId);
                }
                $info = $cron->executeManually($storeData);
                if (!empty($info)) {
                    $executeMoreData = '';
                    if ($info[0] > 0) {
                        $executeMoreData = $this->_url->getUrl(
                            LSR::URL_PATH_EXECUTE,
                            ['joburl' => $jobUrl, 'jobname' => $jobName, 'scope_id' => $scopeId, 'scope' => $scope]
                        );
                    }
                    $this->messageManager->addComplexSuccessMessage(
                        'cronlinkmessage',
                        ['url' => $executeMoreData, 'jobName' => $jobName, 'remaining' => $info[0]]
                    );
                }

                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                return $resultRedirect;
            }
            return $resultPage;
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }
    }

    /**
     * Get Default website Id
     *
     * @return array|string
     */
    public function getDefaultWebsiteId()
    {
        $websiteId = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_MANUAL_CRON_GRID_DEFAULT_WEBSITE);

        if (empty($websiteId)) {
            foreach ($this->systemStoreManager->getWebsiteCollection() as $website) {
                $websiteId = $website->getId();
                break;
            }
        }

        return $websiteId;
    }
}
