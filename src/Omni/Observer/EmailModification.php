<?php

namespace Ls\Omni\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class EmailModification
 * @package Ls\Omni\Observer
 */
class EmailModification implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transportObject = $observer->getEvent()->getData('transportObject');
        $order = $transportObject->getData('order');
        $order->setIncrementId($order->getDocumentId());
    }
}