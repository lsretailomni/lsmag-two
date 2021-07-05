<?php

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
     * @var Logger
     */
    private $logger;

    /**
     * @var Shipment
     */
    private $shipment;

    /**
     * @var Data
     */
    private $helper;

    /**
     * OrderShipment constructor.
     * @param Shipment $shipment
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        Shipment $shipment,
        Data $helper,
        Logger $logger
    ) {
        $this->shipment = $shipment;
        $this->helper   = $helper;
        $this->logger   = $logger;
    }

    /**
     * @inheritdoc
     */
    public function set($orderId, $trackingId, $shipmentProvider, $service, $lines)
    {
        try {
            $data = [
                'orderId'          => $orderId,
                'trackingId'       => $trackingId,
                'shipmentProvider' => $shipmentProvider,
                'service'          => $service,
                'lines'            => $lines,

            ];
            $this->logger->info('OrderShipment', $data);
            return $this->shipment->createShipment($data);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }
}
