<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\GiftCardBalance;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        public PageFactory $resultPageFactory
    ) {
    }

    /**
     * Gift card check balance page entry point
     *
     * @return Page|ResultInterface
     */
    public function execute()
    {
        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->set(__('Check Your Gift Card Balance'));
        return $page;
    }
}
