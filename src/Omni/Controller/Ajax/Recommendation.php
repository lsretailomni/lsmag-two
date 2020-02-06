<?php

namespace Ls\Omni\Controller\Ajax;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Session\SaveHandler;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Recommendation
 * @package Ls\Omni\Controller\Ajax
 */
class Recommendation extends Action
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
     * @var CacheHelper
     */
    public $cacheHelper;

    /**
     * @var SaveHandler
     */
    public $sessionHandler;

    /**
     * Recommendation constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param CacheHelper $cacheHelper
     * @param SaveHandler $sessionHandler
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        CacheHelper $cacheHelper,
        SaveHandler $sessionHandler
    ) {
        $this->resultPageFactory     = $resultPageFactory;
        $this->resultJsonFactory     = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->cacheHelper           = $cacheHelper;
        $this->sessionHandler        = $sessionHandler;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|Redirect|ResultInterface
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
        $cacheKey          = LSR::PRODUCT_RECOMMENDATION_BLOCK_CACHE . $currentProductSku;
        $block             = $this->cacheHelper->getCachedContent($cacheKey);
        if ($block === false) {
            $block = $resultPage->getLayout()
                ->createBlock('Ls\Omni\Block\Product\View\Recommend')
                ->setTemplate('Ls_Omni::product/view/recommendation.phtml')
                ->setData('data', $data)
                ->toHtml();
            if (isset($block)) {
                $this->cacheHelper->persistContentInCache(
                    $cacheKey,
                    $block,
                    [Type::CACHE_TAG],
                    7200
                );
            }
        }
        $result->setData(['output' => $block]);
        return $result;
    }
}
