<?php

namespace Ls\Omni\Controller\Ajax;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Block\Product\View\Recommend;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Helper\SessionHelper;
use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\View\Result\PageFactory;

class Recommendation implements HttpPostActionInterface
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
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param CacheHelper $cacheHelper
     * @param SessionHelper $sessionHelper
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        CacheHelper $cacheHelper,
        SessionHelper $sessionHelper,
        RequestInterface $request
    ) {
        $this->resultPageFactory     = $resultPageFactory;
        $this->resultJsonFactory     = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->cacheHelper           = $cacheHelper;
        $this->sessionHelper         = $sessionHelper;
        $this->request               = $request;
    }

    /**
     * Entry point for controller
     *
     * @return ResponseInterface|Json|Redirect|ResultInterface
     * @throws FileSystemException
     */
    public function execute()
    {
        if (!$this->request->isXmlHttpRequest()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }
        $this->sessionHelper->newSessionHandler("lsrecommend");
        $result            = $this->resultJsonFactory->create();
        $resultPage        = $this->resultPageFactory->create();
        $currentProductSku = $this->request->getParam('currentProduct');
        $data              = ['productSku' => $currentProductSku];
        $cacheKey          = LSR::PRODUCT_RECOMMENDATION_BLOCK_CACHE . $currentProductSku;
        $block             = $this->cacheHelper->getCachedContent($cacheKey);
        if ($block === false) {
            $block = $resultPage->getLayout()
                ->createBlock(Recommend::class)
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
