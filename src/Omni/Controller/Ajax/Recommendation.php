<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Serialize\SerializerInterface;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Model\Cache\Type;
use \Ls\Core\Model\LSR;

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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Ls\Omni\Helper\CacheHelper
     */
    public $cacheHelper;

    /**
     * Recommendation constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param SerializerInterface $serializer
     * @param CacheHelper $cacheHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        SerializerInterface $serializer,
        CacheHelper $cacheHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->serializer = $serializer;
        $this->cacheHelper = $cacheHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
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
        $cacheKey = LSR::PRODUCT_RECOMMENDATION_BLOCK_CACHE . $currentProductSku;
        $block = $this->cacheHelper->getCachedContent($cacheKey);
        if (!$block) {
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
