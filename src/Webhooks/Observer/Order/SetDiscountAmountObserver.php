<?php

namespace Ls\Webhooks\Observer\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Observer to setup initially discount amount for functional api test
 */
class SetDiscountAmountObserver implements ObserverInterface
{
    /**
     * Setting discount amount and base discount amount as 0
     *
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        // Get the order from the event observer
        $order = $observer->getEvent()->getOrder();

        // Set base discount amount and discount amount to zero
        if (!($order->getDiscountAmount() && $order->getBaseDiscountAmount())) {
            $order->setBaseDiscountAmount(0);
            $order->setDiscountAmount(0);
        }

        return $this;
    }
}