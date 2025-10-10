<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Data;

use Ls\Webhooks\Api\Data\OrderShipmentMessageInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class OrderShipment
 *
 * Implementation of OrderShipmentInterface
 */
class OrderShipmentMessage extends AbstractExtensibleModel implements OrderShipmentMessageInterface
{
    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getTrackingId()
    {
        return $this->getData(self::TRACKING_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTrackingId($trackingId)
    {
        return $this->setData(self::TRACKING_ID, $trackingId);
    }

    /**
     * @inheritdoc
     */
    public function getTrackingUrl()
    {
        return $this->getData(self::TRACKING_URL);
    }

    /**
     * @inheritdoc
     */
    public function setTrackingUrl($trackingUrl)
    {
        return $this->setData(self::TRACKING_URL, $trackingUrl);
    }

    /**
     * @inheritdoc
     */
    public function getProvider()
    {
        return $this->getData(self::PROVIDER);
    }

    /**
     * @inheritdoc
     */
    public function setProvider($provider)
    {
        return $this->setData(self::PROVIDER, $provider);
    }

    /**
     * @inheritdoc
     */
    public function getService()
    {
        return $this->getData(self::SERVICE);
    }

    /**
     * @inheritdoc
     */
    public function setService($service)
    {
        return $this->setData(self::SERVICE, $service);
    }

    /**
     * @inheritdoc
     */
    public function getShipmentNo()
    {
        return $this->getData(self::SHIPMENT_NO);
    }

    /**
     * @inheritdoc
     */
    public function setShipmentNo($shipmentNo)
    {
        return $this->setData(self::SHIPMENT_NO, $shipmentNo);
    }

    /**
     * @inheritdoc
     */
    public function getLines()
    {
        return $this->getData(self::LINES);
    }

    /**
     * @inheritdoc
     */
    public function setLines(?array $lines = null)
    {
        return $this->setData(self::LINES, $lines);
    }
}
