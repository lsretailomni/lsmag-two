<?php
declare(strict_types=1);

namespace Ls\Webhooks\Api\Data;

/**
 * Interface OrderShipmentInterface
 *
 * Represents the order shipping payload received by the API.
 */
interface OrderShipmentMessageInterface
{
    public const ORDER_ID = 'OrderId';
    public const TRACKING_ID = 'TrackingId';
    public const TRACKING_URL = 'TrackingUrl';
    public const PROVIDER = 'Provider';
    public const SERVICE = 'Service';
    public const SHIPMENT_NO = 'ShipmentNo';
    public const LINES = 'Lines';

    /**
     * Get the order ID associated with the shipment.
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set the order ID associated with the shipment.
     *
     * @param string|null $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get the tracking ID provided by the shipping carrier.
     *
     * @return string|null
     */
    public function getTrackingId();

    /**
     * Set the tracking ID provided by the shipping carrier.
     *
     * @param string|null $trackingId
     * @return $this
     */
    public function setTrackingId($trackingId);

    /**
     * Get the tracking URL where shipment can be tracked.
     *
     * Example: "http://www.flatrate.com".
     *
     * @return string|null
     */
    public function getTrackingUrl();

    /**
     * Set the tracking URL where shipment can be tracked.
     *
     * @param string|null $trackingUrl
     * @return $this
     */
    public function setTrackingUrl($trackingUrl);

    /**
     * Get the shipping provider name.
     *
     * Example: "FLATRATE".
     *
     * @return string|null
     */
    public function getProvider();

    /**
     * Set the shipping provider name.
     *
     * @param string|null $provider
     * @return $this
     */
    public function setProvider($provider);

    /**
     * Get the shipping service used for the order.
     *
     * Example: "FLATRATE".
     *
     * @return string|null
     */
    public function getService();

    /**
     * Set the shipping service used for the order.
     *
     * @param string|null $service
     * @return $this
     */
    public function setService($service);

    /**
     * Get the shipment number identifier.
     *
     * @return string|null
     */
    public function getShipmentNo();

    /**
     * Set the shipment number identifier.
     *
     * @param string|null $shipmentNo
     * @return $this
     */
    public function setShipmentNo($shipmentNo);

    /**
     * Get the order lines included in this shipment.
     *
     * @return \Ls\Webhooks\Api\Data\OrderLineInterface[]|null
     */
    public function getLines();

    /**
     * Set the order lines included in this shipment.
     *
     * @param \Ls\Webhooks\Api\Data\OrderLineInterface[]|null $lines
     * @return $this
     */
    public function setLines(?array $lines = null);
}
