<?php

namespace Ls\Omni\Controller\Stock;

use \Ls\Omni\Helper\StockHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class Store
 * @package Ls\Omni\Controller\Stock
 */
class Store extends Action
{
    /**
     * @var \Magento\Framework\App\Request\Http\Proxy
     */
    public $request;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

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
     * Store constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Request\Http\Proxy $request
     * @param ScopeConfigInterface $scopeConfig
     * @param Proxy $session
     * @param StockHelper $stockHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Request\Http\Proxy $request,
        ScopeConfigInterface $scopeConfig,
        Proxy $session,
        StockHelper $stockHelper
    ) {
        $this->request           = $request;
        $this->scopeConfig       = $scopeConfig;
        $this->session           = $session;
        $this->stockHelper       = $stockHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * execute
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) {
            $selectedStore      = $this->request->getParam('storeid');
            $items              = $this->session->getQuote()->getAllVisibleItems();
            $stockCollection    = [];
            $notAvailableNotice = __("Please check other stores or remove the not available item(s) from your ");
            foreach ($items as &$item) {
                $sku              = $item->getSku();
                $parentProductSku = $childProductSku = "";
                if (strpos($sku, '-') !== false) {
                    $parentProductSku = explode('-', $sku)[0];
                    $childProductSku  = explode('-', $sku)[1];
                } else {
                    $parentProductSku = $sku;
                }
                $stockCollection[$sku]["name"] = $item->getName();
                $stockCollection[$sku]["qty"]  = $item->getQty();
                $item                          = ["parent" => $parentProductSku, "child" => $childProductSku];
            }
            $response = $this->stockHelper->getAllItemsStockInSingleStore($selectedStore, $items);
            if ($response) {
                if (!is_array($response->getInventoryResponse())) {
                    $response = [$response->getInventoryResponse()];
                } else {
                    $response = $response->getInventoryResponse();
                }
                foreach ($response as $item) {
                    $actualQty = ceil($item->getQtyInventory());
                    $sku       = $item->getItemId() .
                        (($item->getVariantId()) ? '-' . $item->getVariantId() : '');
                    if ($actualQty > 0) {
                        $stockCollection[$sku]["status"]  = "1";
                        $stockCollection[$sku]["display"] = __("This item is available");
                        if ($stockCollection[$sku]["qty"] > $actualQty) {
                            $stockCollection[$sku]["status"]  = "0";
                            $stockCollection[$sku]["display"] = __(
                                "You have selected %1 quantity for this item.
                                 We only have %2 quantity available in stock for this store.
                                 Please update this item quantity in cart.",
                                $stockCollection[$sku]["qty"],
                                $actualQty
                            );
                        }
                    } else {
                        $stockCollection[$sku]["status"]  = "0";
                        $stockCollection[$sku]["display"] = __("This item is not available");
                    }
                }
                $result = $result->setData(
                    ["remarks" => $notAvailableNotice, "stocks" => $stockCollection]
                );
            } else {
                $notAvailableNotice = __("Oops! Unable to do stock lookup currently.");
                $result             = $result->setData(
                    ["remarks" => $notAvailableNotice, "stocks" => null]
                );
            }
        }
        return $result;
    }
}
