<?php

namespace Ls\Omni\Plugin\Email\Sender;

use Magento\Sales\Model\Order\Shipment;

/**
 * Class ShipmentSender
 * @package Ls\Omni\Plugin\Email\Sender
 */
class ShipmentSender
{

    /**
     * @param $subject
     * @param Shipment $shipment
     * @param false $forceSyncMode
     * @return array
     */
    public function beforeSend($subject, Shipment $shipment, $forceSyncMode = false)
    {
        if (!empty($shipment->getOrder()->getDocumentId())) {
            $shipment->getOrder()->setIncrementId($shipment->getOrder()->getDocumentId());
        }
        return [$shipment, $forceSyncMode];
    }
}
