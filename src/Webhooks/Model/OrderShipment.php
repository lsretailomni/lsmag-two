<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model;

use Exception;
use \Ls\Webhooks\Api\OrderShipmentInterface;
use \Ls\Webhooks\Model\Order\Shipment;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;

/**
 * Class for handling shipment
 */
class OrderShipment implements OrderShipmentInterface
{
    /**
     * @param Shipment $shipment
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        public Shipment $shipment,
        public Data $helper,
        public Logger $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function set(\Ls\Webhooks\Api\Data\OrderShipmentMessageInterface $orderShipping)
    {
        try {
            $logOriginal = [
                'OrderId'     => $orderShipping->getOrderId(),
                'ShipmentNo'  => $orderShipping->getShipmentNo(),
                'TrackingId'  => $orderShipping->getTrackingId(),
                'TrackingUrl' => $orderShipping->getTrackingUrl(),
                'Provider'    => $orderShipping->getProvider(),
                'Service'     => $orderShipping->getService(),
                'Lines'       => $orderShipping->getLines(),

            ];
            $data        = [
                'orderId'             => $orderShipping->getOrderId(),
                'lsCentralShippingId' => $orderShipping->getShipmentNo(),
                'trackingId'          => $orderShipping->getTrackingId(),
                'shipmentProvider'    => $orderShipping->getProvider(),
                'service'             => $orderShipping->getService(),
                'lines'               => $orderShipping->getLines(),

            ];
            $this->logger->info('OrderShipment', $logOriginal);
            return $this->shipment->createShipment($data);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }
}
