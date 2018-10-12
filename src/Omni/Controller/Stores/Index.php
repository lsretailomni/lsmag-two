<?php

namespace Ls\Omni\Controller\Stores;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Replication\Model\ReplStore
     */
    protected  $_replStoreFactory;

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {

        //exit();
        return $this->resultPageFactory->create();
        //$this->_view->loadLayout();
        //$this->_view->renderLayout();

    }

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Ls\Replication\Model\ReplStore $replStore
    )
    {

        $this->resultPageFactory = $resultPageFactory;
        $this->_replStoreFactory = $replStore;
        parent::__construct($context);
    }

}
