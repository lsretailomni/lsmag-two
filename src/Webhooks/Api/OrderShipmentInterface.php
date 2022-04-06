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
     * @param string $OrderId
     * @param string $ShipmentId
     * @param string $TrackingId
     * @param string $TrackingUrl
     * @param string $Provider
     * @param string $Service
     * @param mixed $Lines
     * @return mixed
     */
    public function set($OrderId, $ShipmentId, $TrackingId, $TrackingUrl, $Provider, $Service, $Lines);
}
