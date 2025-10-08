<?php
declare(strict_types=1);

namespace Ls\Replication\Controller\Adminhtml\Grids;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Phrase;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

abstract class AbstractGrid extends Action
{
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        public PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
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
        $resultPage->getConfig()->getTitle()->prepend($this->getTitle());
        return $resultPage;
    }

    /**
     * Get title
     *
     * @return Phrase
     */
    abstract public function getTitle();
}
