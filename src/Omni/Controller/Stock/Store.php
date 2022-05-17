<?php

namespace Ls\Omni\Controller\Stock;

use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\StockHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Controller to accept request for stock lookup for each item in the quote
 */
class Store extends Action
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

    /** @var ItemHelper $itemHelper */
    public $itemHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Request\Http\Proxy $request
     * @param Proxy $session
     * @param StockHelper $stockHelper
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Request\Http\Proxy $request,
        Proxy $session,
        StockHelper $stockHelper,
        ItemHelper $itemHelper
    ) {
        $this->request           = $request;
        $this->session           = $session;
        $this->stockHelper       = $stockHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->itemHelper        = $itemHelper;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if ($this->getRequest()->isAjax()) {
            $selectedStore      = $this->request->getParam('storeid');
            $items              = $this->session->getQuote()->getAllVisibleItems();
            $notAvailableNotice = __('Please check other stores or remove the not available item(s) from your ');

            list($response, $stockCollection) = $this->stockHelper->getGivenItemsStockInGivenStore(
                $items,
                $selectedStore
            );

            if ($response) {
                if (is_object($response)) {
                    if (!is_array($response->getInventoryResponse())) {
                        $response = [$response->getInventoryResponse()];
                    } else {
                        $response = $response->getInventoryResponse();
                    }
                }

                $stockCollection = $this->stockHelper->updateStockCollection($response, $stockCollection);
                $result = $result->setData(
                    ['remarks' => $notAvailableNotice, 'stocks' => $stockCollection]
                );
            } else {
                $notAvailableNotice = __('Oops! Unable to do stock lookup currently.');
                $result             = $result->setData(
                    ['remarks' => $notAvailableNotice, 'stocks' => null]
                );
            }
        }

        return $result;
    }
}
