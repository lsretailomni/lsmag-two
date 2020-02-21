<?php

namespace Ls\Omni\Controller\Ajax;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Helper\SessionHelper;
use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
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
     * @var SessionHelper
     */
    public $sessionHelper;

    /**
     * Recommendation constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param CacheHelper $cacheHelper
     * @param SessionHelper $sessionHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        CacheHelper $cacheHelper,
        SessionHelper $sessionHelper
    ) {
        $this->resultPageFactory     = $resultPageFactory;
        $this->resultJsonFactory     = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->cacheHelper           = $cacheHelper;
        $this->sessionHelper         = $sessionHelper;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|Redirect|ResultInterface
     * @throws FileSystemException
     */
    public function execute()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }
        $this->sessionHelper->newSessionHandler("lsrecommend");
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
