<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Ajax;

use \Ls\Omni\Block\Product\View\Discount\Proactive;
use \Ls\Omni\Helper\SessionHelper;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;

class ProactiveDiscountsAndCoupons implements HttpGetActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param SessionHelper $sessionHelper
     * @param RequestInterface $request
     */
    public function __construct(
        public PageFactory $resultPageFactory,
        public JsonFactory $resultJsonFactory,
        public RedirectFactory $resultRedirectFactory,
        public SessionHelper $sessionHelper,
        public RequestInterface $request
    ) {
    }

    /**
     * Entry point for this controller
     *
     * @return Json|ResultInterface
     * @throws FileSystemException
     */
    public function execute()
    {
        if (!$this->request->isXmlHttpRequest()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');

            return $resultRedirect;
        }
        $this->sessionHelper->newSessionHandler("lsproactivediscounts");
        $result = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $currentProductSku = $this->request->getParam('currentProduct');
        $data = ['productSku' => $currentProductSku];

        $block = $resultPage->getLayout()
            ->createBlock(Proactive::class)
            ->setTemplate('Ls_Omni::product/view/proactive.phtml')
            ->setData('data', $data)
            ->toHtml();
        $result->setData(['output' => $block]);

        return $result;
    }
}
