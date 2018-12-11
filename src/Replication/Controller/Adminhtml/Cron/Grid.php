<?php
/**
 * LSRetail
 * @package     Ls_Replication
 * @copyright   Copyright (c) 2018 LSRetail
 */

namespace Ls\Replication\Controller\Adminhtml\Cron;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;
use \Magento\Framework\Controller\ResultFactory;
use Magento\Framework\ObjectManagerInterface;

class Grid extends Action
{
    /** Url path */
    const URL_PATH_Execute = 'ls_repl/cron/grid';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ObjectManager
     */

    protected $_objectManager;

    protected $_replicationHelper;


    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_objectManager = $objectManager;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Cron Listing '));
            $jobUrl = $this->_request->getParam('joburl');
            $jobName = $this->_request->getParam('jobname');
            if ($jobUrl != "") {
                $cron = $this->_objectManager->create($jobUrl);
                $info = $cron->executeManually();
                if (!empty($info)) {
                    $executeMoreData = '';
                    if ($info[0] > 0) {
                        $executeMoreData=$this->_url->getUrl(self::URL_PATH_Execute, ['joburl' => $jobUrl, 'jobname' => $jobName]);
                    }
                    $this->messageManager->addComplexSuccessMessage('cronlinkmessage',['url'=>$executeMoreData,'jobName'=>$jobName,'remaining'=>$info[0]]);
                }
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                return $resultRedirect;
            } else {
                return $resultPage;
            }
        } catch (\Exception $e) {
        }
    }
}
