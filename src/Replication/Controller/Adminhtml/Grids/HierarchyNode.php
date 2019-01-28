<?php
/**
 * LSRetail
 * @package     Ls_Replication
 * @copyright   Copyright (c) 2018 LSRetail
 */
namespace Ls\Replication\Controller\Adminhtml\Grids;

/**
 * Class HierarchyNode
 * @package Ls\Replication\Controller\Adminhtml\Grids
 */
class HierarchyNode extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $resultPageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Load the grid defined through grid component
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        //Set the header title of grid
        $resultPage->getConfig()->getTitle()->prepend(__('Hierarchy Node Replication'));
        return $resultPage;
    }
}
