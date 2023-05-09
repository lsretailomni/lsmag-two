<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class RecommendationCart
 * @package Ls\Omni\Controller\Ajax
 */
class RecommendationCart implements HttpPostActionInterface
{

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * @var RedirectFactory
     */
    public $resultRedirectFactory;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * RecommendationCart constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        RequestInterface $request
    ) {
        $this->resultPageFactory     = $resultPageFactory;
        $this->resultJsonFactory     = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->request               = $request;
    }

    /**
     * @return Json
     */
    public function execute()
    {
        if ($this->request->getMethod() !== 'POST' || !$this->request->isXmlHttpRequest()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }
        $result     = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $block      = $resultPage->getLayout()
            ->createBlock('Ls\Omni\Block\Product\Recommend')
            ->setTemplate('Ls_Omni::checkout/cart/view/recommendation.phtml')
            ->toHtml();
        $result->setData(['output' => $block]);
        return $result;
    }
}
