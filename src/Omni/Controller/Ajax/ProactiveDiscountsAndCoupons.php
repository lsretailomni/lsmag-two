<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Session\SaveHandler;
use Magento\Framework\View\Result\PageFactory;

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
     * @var SaveHandler
     */
    public $sessionHandler;

    /**
     * ProactiveDiscountsAndCoupons constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param SaveHandler $sessionHandler
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        SaveHandler $sessionHandler
    ) {
        $this->resultPageFactory     = $resultPageFactory;
        $this->resultJsonFactory     = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->sessionHandler        = $sessionHandler;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $tmpSessionDir = ini_get("session.save_path");
        $this->sessionHandler->close();
        $this->sessionHandler->open($tmpSessionDir, "admin");
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }

        $result            = $this->resultJsonFactory->create();
        $resultPage        = $this->resultPageFactory->create();
        $currentProductSku = $this->getRequest()->getParam('currentProduct');
        $data              = ['productSku' => $currentProductSku];
        $blockCoupons      = $resultPage->getLayout()
            ->createBlock('Ls\Omni\Block\Product\View\Discount\Proactive')
            ->setTemplate('Ls_Omni::product/view/coupons.phtml')
            ->setData('data', $data)
            ->toHtml();
        $data              = array_merge($data, ['coupons' => $blockCoupons]);
        $block             = $resultPage->getLayout()
            ->createBlock('Ls\Omni\Block\Product\View\Discount\Proactive')
            ->setTemplate('Ls_Omni::product/view/proactive.phtml')
            ->setData('data', $data)
            ->toHtml();
        $result->setData(['output' => $block]);
        return $result;
    }
}
