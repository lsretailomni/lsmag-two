<?php

namespace Ls\Replication\Controller\Adminhtml\Grids;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class HierarchyNode
 * @package Ls\Replication\Controller\Adminhtml\Grids
 */
class HierarchyNode extends Action
{
    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Load the grid defined through grid component
     *
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        //Set the header title of grid
        $resultPage->getConfig()->getTitle()->prepend(__('Hierarchy Node Replication'));
        return $resultPage;
    }
}
