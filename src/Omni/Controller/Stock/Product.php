<?php

namespace Ls\Omni\Controller\Stock;

use \Ls\Omni\Helper\StockHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * Product constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Request\Http\Proxy $request
     * @param Proxy $session
     * @param StockHelper $stockHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Request\Http\Proxy $request,
        Proxy $session,
        StockHelper $stockHelper
    ) {
        $this->request           = $request;
        $this->session           = $session;
        $this->stockHelper       = $stockHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
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
            $result         = $result->setData(
                [
                    'title'   => $notAvailableNoticeTitle,
                    'content' => $notAvailableNoticeContent,
                    'stocks'  => $customResponse
                ]
            );
        }
        return $result;
    }
}
