<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class RecommendationCart
 * @package Ls\Omni\Controller\Ajax
 */
class Coupons implements HttpPostActionInterface
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
     * @return ResponseInterface|Json|Redirect|ResultInterface
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
            ->createBlock('Ls\Omni\Block\Cart\Coupons')
            ->setTemplate('Ls_Omni::cart/coupons-listing.phtml')
            ->toHtml();
        $result->setData(['output' => $block]);
        return $result;
    }
}
