<?php

namespace Ls\Omni\Controller\Stock;

/**
 * Class Product
 * @package Ls\Omni\Controller\Stock
 */

class Product extends \Magento\Framework\App\Action\Action
{
    /**
     * @var $_request
     */
    protected $_request;
    /**
     * @var $_session
     */
    protected $_session;
    /**
     * @var $_stockHelper
     */
    protected $_stockHelper;
    /**
     * @var $_resultJsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * Product constructor
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory
     *        $resultJsonFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Checkout\Model\Session $session
     * @param \Ls\Omni\Helper\StockHelper $stockHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Checkout\Model\Session $session,
        \Ls\Omni\Helper\StockHelper $stockHelper
    ) {
        $this->_request = $request;
        $this->_session = $session;
        $this->_stockHelper = $stockHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * execute
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        $notAvailableNoticeTitle = __(
            \Ls\Core\Model\LSR::MSG_NOT_AVAILABLE_NOTICE_TITLE
        );
        $notAvailableNoticeContent = __(
            \Ls\Core\Model\LSR::MSG_NOT_AVAILABLE_NOTICE_CONTENT
        );
        if ($this->getRequest()->isAjax()) {
            $storesNavId = [];
            $productSku = $this->_request->getParam('sku');
            $simpleProductId = $this->_request->getParam('id');
            $response = $this->_stockHelper->getAllStoresItemInStock(
                $simpleProductId,
                $productSku
            );
            if (is_array($response->getStore())) {
                foreach ($response->getStore() as $each) {
                    $storesNavId[] = $each->getId();
                }
            } else {
                $storesNavId[] = $response->getStore()->getId();
            }

            $customResponse = $this->_stockHelper->getAllStoresFromReplTable(
                $storesNavId
            );
            $result = $result->setData(
                [
                    "title" => $notAvailableNoticeTitle,
                    "content" => $notAvailableNoticeContent,
                    "stocks" => $customResponse
                ]
            );
        }
        return $result;
    }
}
