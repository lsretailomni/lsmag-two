<?php

namespace Ls\Omni\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Core\Model\LSR;
use Magento\Sales\Model\Order;

/**
 * Class OrderCancelObserver
 */
class OrderCancelObserver implements ObserverInterface
{

    /**
     * @var LSR
     */
    private $lsr;
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * OrderCancelObserver constructor.
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        LSR $lsr,
        OrderHelper $orderHelper

    ) {
        $this->lsr = $lsr;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $documentId = $order->getDocumentId();
        $websiteId = $order->getStore()->getWebsiteId();
        /**
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($websiteId,'website')) {
            if (!empty($documentId)) {
                $websiteId = $order->getStore()->getWebsiteId();
                $webStore = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $websiteId);
                $this->orderHelper->OrderCancel($documentId, $webStore);
            }
        }

        return $this;
    }
}
