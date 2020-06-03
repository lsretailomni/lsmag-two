<?php

namespace Ls\Omni\Controller\Stock;

use Ls\Omni\Helper\StockHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Product
 * @package Ls\Omni\Controller\Stock
 */
class Product extends Action
{

    /**
     * @var \Magento\Framework\App\Request\Http\Proxy
     */
    public $request;

    /**
     * @var Proxy
     */
    public $session;

    /**
     * @var StockHelper
     */
    public $stockHelper;

    /**
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * Product constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\App\Request\Http\Proxy $request
     * @param Proxy $session
     * @param StockHelper $stockHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PageFactory $resultPageFactory,
        \Magento\Framework\App\Request\Http\Proxy $request,
        Proxy $session,
        StockHelper $stockHelper
    ) {
        $this->request           = $request;
        $this->session           = $session;
        $this->stockHelper       = $stockHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result     = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        // @codingStandardsIgnoreStart
        $notAvailableNoticeTitle   = __("Notice");
        $notAvailableNoticeContent = __("This item is only available online.");
        // @codingStandardsIgnoreEnd
        if ($this->getRequest()->isAjax()) {
            $storesNavId     = [];
            $productSku      = $this->request->getParam('sku');
            $simpleProductId = $this->request->getParam('id');
            $response        = $this->stockHelper->getAllStoresItemInStock(
                $simpleProductId,
                $productSku
            );
            if ($response !== null) {
                foreach ($response->getInventoryResponse() as $each) {
                    if ($each->getQtyInventory() > 0) {
                        $storesNavId[] = $each->getStoreId();
                    }
                }
            }
            $customResponse = $this->stockHelper->getAllStoresFromReplTable(
                $storesNavId
            );

            $storesData = $resultPage->getLayout()->createBlock('Ls\Omni\Block\Stores\Stores')
                ->setTemplate('Ls_Omni::stores/stores.phtml')
                ->setData('data', $customResponse)
                ->toHtml();

            $stores               = $customResponse->toArray();
            $stores['storesInfo'] = $storesData;
            $encodedStores        = \Zend_Json::encode($stores);

            $result = $result->setData(
                [
                    'title'   => $notAvailableNoticeTitle,
                    'content' => $notAvailableNoticeContent,
                    'stocks'  => $encodedStores,
                ]
            );
        }
        return $result;
    }
}
