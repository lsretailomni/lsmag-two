<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Serialize\SerializerInterface;
use \Ls\Omni\Model\Cache\Type;

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
     * @var Type
     */
    public $cacheType;

    /**
     * Recommendation constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param Type $cacheType
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        SerializerInterface $serializer,
        Type $cacheType
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->serializer = $serializer;
        $this->cacheType = $cacheType;
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
        $cacheKey = 'product_recommendation_' . $currentProductSku;
        $block = $this->cacheType->load($cacheKey);
        if (empty($block)) {
            $block = $resultPage->getLayout()
                ->createBlock('Ls\Omni\Block\Product\View\Recommend')
                ->setTemplate('Ls_Omni::product/view/recommendation.phtml')
                ->setData('data', $data)
                ->toHtml();
            if (isset($block)) {
                $this->cacheType->save($this->serializer->serialize($block), $cacheKey, [Type::CACHE_TAG], 7200);
            }
        } else {
            $block = $this->serializer->unserialize($block);
        }
        $result->setData(['output' => $block]);
        return $result;
    }
}