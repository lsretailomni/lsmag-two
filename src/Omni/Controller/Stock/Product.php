<?php

namespace Ls\Omni\Controller\Stock;

/**
 * Class Product
 * @package Ls\Omni\Controller\Stock
 */
class Product extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\Request\Http\Proxy
     */
    public $request;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    public $session;

    /**
     * @var \Ls\Omni\Helper\StockHelper
     */
    public $stockHelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    public $resultJsonFactory;

    /**
     * Product constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Request\Http\Proxy $request
     * @param \Magento\Checkout\Model\Session\Proxy $session
     * @param \Ls\Omni\Helper\StockHelper $stockHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Request\Http\Proxy $request,
        \Magento\Checkout\Model\Session\Proxy $session,
        \Ls\Omni\Helper\StockHelper $stockHelper
    ) {
        $this->request           = $request;
        $this->session           = $session;
        $this->stockHelper       = $stockHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
