<?php

namespace Ls\Webhooks\Api;

/**
 * Interface OrderShipmentInterface
 * @api
 */
interface OrderShipmentInterface
{
    /**
     * Set order shipment API
     * @param string $orderId
     * @param string $trackingId
     * @param string $shipmentProvider
     * @param string $service
     * @param mixed $lines
     * @return mixed
     */
    public function set($orderId, $trackingId, $shipmentProvider, $service, $lines);
}
