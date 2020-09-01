<?php

namespace Ls\Omni\Plugin\Email\Sender;

use Magento\Sales\Model\Order;

/**
 * Class OrderSender
 * @package Ls\Omni\Plugin\Email\Sender
 */
class OrderSender
{

    /**
     * @param $subject
     * @param $proceed
     * @param Order $order
     * @param false $forceSyncMode
     * @return mixed
     */
    public function aroundSend($subject, $proceed, Order $order, $forceSyncMode = false)
    {
        $incrementId = $order->getIncrementId();
        if (!empty($order->getDocumentId())) {
            $order->setIncrementId($order->getDocumentId());
        }
        $result = $proceed($order, $forceSyncMode);
        $order->setIncrementId($incrementId);
        return $result;
    }
}
