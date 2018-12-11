<?php

namespace Ls\Omni\Observer;

use Magento\Framework\Event\ObserverInterface;

class DataAssignObserver implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        
        $order->setPickupDate($quote->getPickupDate());
        
        if ($quote->getPickupStore()) {
            $order->setPickupStore($quote->getPickupStore());
        }
        return $this;
    }
}
