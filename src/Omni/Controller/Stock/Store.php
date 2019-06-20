<?php

namespace Ls\Omni\Controller\Stock;

/**
 * Class Store
 * @package Ls\Omni\Controller\Stock
 */

class Store extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Request\Http\Proxy
     */
    public $request;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

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
     * Store constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Request\Http\Proxy $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session\Proxy $session
     * @param \Ls\Omni\Helper\StockHelper $stockHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Request\Http\Proxy $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session\Proxy $session,
        \Ls\Omni\Helper\StockHelper $stockHelper
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->stockHelper = $stockHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * execute
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) {
            $selectedStore = $this->request->getParam('storeid');
            $items = $this->session->getQuote()->getAllVisibleItems();
            $stockCollection = [];
            $notAvailableNotice = __(
                "Please check other stores or remove
                 the not available item(s) from your "
            );
            foreach ($items as &$item) {
                $sku = $item->getSku();
                $parentProductSku = $childProductSku = "";
                if (strpos($sku, '-') !== false) {
                    $parentProductSku = explode('-', $sku)[0];
                    $childProductSku = explode('-', $sku)[1];
                } else {
                    $parentProductSku = $sku;
                }
                $stockCollection[$sku]["name"] = $item->getName();
                $item = ["parent" => $parentProductSku, "child" => $childProductSku];
            }
            $response = $this->stockHelper->getAllItemsStockInSingleStore($selectedStore, $items);
            if ($response) {
                if (!is_array($response->getInventoryResponse())) {
                    $response = [$response->getInventoryResponse()];
                } else {
                    $response = $response->getInventoryResponse();
                }
                foreach ($response as $item) {
                    $actualQty = ceil($item->getQtyActualInventory());
                    $sku = $item->getItemId() .
                        (($item->getVariantId()) ? '-' . $item->getVariantId() : '');
                    if ($actualQty > 0) {
                        $stockCollection[$sku]["status"] = "1";
                        $stockCollection[$sku]["display"] = __("This item is available");
                    } else {
                        $stockCollection[$sku]["status"] = "0";
                        $stockCollection[$sku]["display"] = __("This item is not available");
                    }
                }
                $result = $result->setData(
                    ["remarks" => $notAvailableNotice, "stocks" => $stockCollection]
                );
            }
        }
        return $result;
    }
}
