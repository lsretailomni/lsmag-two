<?php

namespace Ls\Omni\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class EmailModification
 * @package Ls\Omni\Observer
 */
class EmailModification implements ObserverInterface
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $transportObject = $observer->getEvent()->getData('transportObject');
        $order           = $transportObject->getData('order');
        if (!empty($order->getDocumentId())) {
            $order->setIncrementId($order->getDocumentId());
        }
    }
}
