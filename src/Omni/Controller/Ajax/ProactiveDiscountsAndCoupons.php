<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * Class Recommendation
 * @package Ls\Omni\Controller\Ajax
 */
class ProactiveDiscountsAndCoupons extends Action
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
     * Recommendation constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        if ($this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }

        $result = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $currentProductSku = $this->getRequest()->getParam('currentProduct');
        $data = ['productSku' => $currentProductSku];
        $blockCoupons = $resultPage->getLayout()
            ->createBlock('Ls\Omni\Block\Product\View\Discount\Proactive')
            ->setTemplate('Ls_Omni::product/view/coupons.phtml')
            ->setData('data', $data)
            ->toHtml();
        $data =array_merge($data, ['coupons'=>$blockCoupons]);
        $block = $resultPage->getLayout()
            ->createBlock('Ls\Omni\Block\Product\View\Discount\Proactive')
            ->setTemplate('Ls_Omni::product/view/proactive.phtml')
            ->setData('data', $data)
            ->toHtml();
        $result->setData(['output' => $block]);
        return $result;
    }
}
