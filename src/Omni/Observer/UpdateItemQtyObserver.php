<?php

namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\StockHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class CartObserver
 */
class UpdateItemQtyObserver implements ObserverInterface
{

    /** @var StockHelper */
    private $stockHelper;

    /**
     * @var Proxy
     */
    private $checkoutSession;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var Json
     */
    private $json;

    /**
     * UpdateItemQtyObserver constructor.
     * @param StockHelper $stockHelper
     * @param Proxy $checkoutSession
     * @param LSR $LSR
     */
    public function __construct(
        StockHelper $stockHelper,
        Proxy $checkoutSession,
        Json $json,
        LSR $LSR
    ) {
        $this->stockHelper     = $stockHelper;
        $this->checkoutSession = $checkoutSession;
        $this->json            = $json;
        $this->lsr             = $LSR;
    }

    /**
     * Method for validating quantity for items in cart
     * @param Observer $observer
     * @return $this|void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /*
          * Adding condition to only process if LSR is enabled.
          */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $cartData = $observer->getRequest()->getParam('cart');
            foreach ($cartData as $itemId => $itemInfo) {
                $item = $this->checkoutSession->getQuote()->getItemById($itemId);
                if (!$item->getHasError()) {
                    $qty = isset($itemInfo['qty']) ? (double)$itemInfo['qty'] : 0;
                    if ($item) {
                        try {
                            $this->stockHelper->validateQty($qty, $item);
                        } catch (LocalizedException $e) {
                            $controllerAction  = $observer->getData('controller_action');
                            $controllerAction->getResponse()->representJson(
                                $this->json->serialize($this->getResponseData($e->getMessage()))
                            );
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Returns response data.
     *
     * @param string $error
     * @return array
     */
    public function getResponseData(string $error = ''): array
    {
        $response = ['success' => true];

        if (!empty($error)) {
            $response = [
                'success'       => false,
                'error_message' => $error,
            ];
        }

        return $response;
    }
}
