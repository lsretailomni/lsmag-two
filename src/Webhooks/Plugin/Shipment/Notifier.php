<?php

namespace Ls\Webhooks\Plugin\Shipment;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Shipment\Notifier as ShipmentNotifier;

/**
 * Plugin for adding document id in shipment email
 */
class Notifier
{
    /**
     * Intercept the notify method for adding document id in email
     * @param ShipmentNotifier $subject
     * @param OrderInterface $order
     * @param ShipmentInterface $shipment
     * @param ShipmentCommentCreationInterface|null $comment
     * @param bool $forceSyncMode
     * @return array[]
     */
    public function beforeNotify(
        ShipmentNotifier $subject,
        OrderInterface $order,
        ShipmentInterface $shipment,
        ShipmentCommentCreationInterface $comment = null,
        $forceSyncMode = false
    ) {
        $order->setIncrementId($order->getDocumentId());
        return [$order, $shipment, $comment, $forceSyncMode];
    }
}
