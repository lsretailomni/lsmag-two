<?php

namespace Ls\Omni\Controller\Stock;

use \Ls\Omni\Block\Stores\Stores;
use \Ls\Omni\Helper\AbstractHelperOmni;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller to accept request for stock lookup for each item in the quote
 */
class Store implements HttpPostActionInterface
{
    /**
     * @var Http
     */
    public $request;

    /**
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @var AbstractHelperOmni
     */
    public $abstractHelperOmni;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param Http $request
     * @param PageFactory $resultPageFactory
     * @param AbstractHelperOmni $abstractHelperOmni
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Http $request,
        PageFactory $resultPageFactory,
        AbstractHelperOmni $abstractHelperOmni
    ) {
        $this->request            = $request;
        $this->resultJsonFactory  = $resultJsonFactory;
        $this->resultPageFactory  = $resultPageFactory;
        $this->abstractHelperOmni = $abstractHelperOmni;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result             = $this->resultJsonFactory->create();
        $stockHelper        = $this->abstractHelperOmni->getStockHelper();
        $dataHelper         = $this->abstractHelperOmni->getDataHelper();
        $checkoutSession    = $this->abstractHelperOmni->getCheckoutSession();

        if ($this->request->isAjax()) {
            $selectedStore      = $this->request->getParam('storeid');
            $items              = $checkoutSession->getQuote()->getAllVisibleItems();
            $notAvailableNotice = __('Please check other stores or remove the not available item(s) from your ');
            list($response, $stockCollection) = $stockHelper->getGivenItemsStockInGivenStore(
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

                $stockCollection = $stockHelper->updateStockCollection($response, $stockCollection);
                $hours           = $dataHelper->getStoreHours($selectedStore);
                $resultPage      = $this->resultPageFactory->create();
                $storeHoursHtml  = $resultPage->getLayout()->createBlock(Stores::class)
                    ->setTemplate('Ls_Omni::stores/hours.phtml')
                    ->setData(['hours' => $hours])
                    ->toHtml();
                $result          = $result->setData(
                    [
                        'remarks'        => $notAvailableNotice,
                        'stocks'         => $stockCollection,
                        'storeHoursHtml' => $storeHoursHtml
                    ]
                );
            } else {
                $notAvailableNotice = __('Oops! Unable to do stock lookup currently.');
                $result             = $result->setData(
                    ['remarks' => $notAvailableNotice, 'stocks' => null, 'storeHoursHtml' => null]
                );
            }
        }

        return $result;
    }
}
