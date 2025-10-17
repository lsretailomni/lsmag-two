<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Coupons implements HttpPostActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     */
    public function __construct(
        public PageFactory $resultPageFactory,
        public JsonFactory $resultJsonFactory,
        public RedirectFactory $resultRedirectFactory,
        public RequestInterface $request
    ) {
    }

    /**
     * Entry point for the controller, responsible to return coupon recommendations
     *
     * @return Json|Redirect
     */
    public function execute()
    {
        $isPost    = $this->request->isPost();
        if (!$isPost || !$this->request->isXmlHttpRequest()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }

        $result     = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $block      = $resultPage->getLayout()
            ->createBlock(\Ls\Omni\Block\Cart\Coupons::class)
            ->setTemplate('Ls_Omni::cart/coupons-listing.phtml')
            ->toHtml();
        $result->setData(['output' => $block]);

        return $result;
    }
}
