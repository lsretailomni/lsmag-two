<?php

namespace Ls\Webhooks\Model\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use \Ls\Webhooks\Helper\Data;

/**
 * class to create shipment through webhook
 */
class Shipment
{
    /**
     * @var ShipOrderInterface
     */
    private $shipOrderInterface;

    /**
     * @var ShipmentItemCreationInterface
     */
    private $shipmentItemCreationInterface;

    /**
     * @var ShipmentInterface
     */
    private $shipmentInterface;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var TrackFactory
     */
    private $trackFactory;

    /**
     * @var ShipmentCommentCreationInterface
     */
    private $shipmentCommentCreation;

    /**
     * Shipment constructor.
     * @param ShipOrderInterface $shipOrderInterface
     * @param ShipmentItemCreationInterface $shipmentItemCreationInterface
     * @param ShipmentInterface $shipmentInterface
     * @param TrackFactory $trackFactory
     * @param ShipmentCommentCreationInterface $shipmentCommentCreation
     * @param Data $helper
     */
    public function __construct(
        ShipOrderInterface $shipOrderInterface,
        ShipmentItemCreationInterface $shipmentItemCreationInterface,
        ShipmentInterface $shipmentInterface,
        TrackFactory $trackFactory,
        ShipmentCommentCreationInterface $shipmentCommentCreation,
        Data $helper
    ) {
        $this->shipOrderInterface            = $shipOrderInterface;
        $this->shipmentItemCreationInterface = $shipmentItemCreationInterface;
        $this->shipmentInterface             = $shipmentInterface;
        $this->trackFactory                  = $trackFactory;
        $this->shipmentCommentCreation       = $shipmentCommentCreation;
        $this->helper                        = $helper;
    }

    /**
     * Creating shipment in Magento
     * @param $data
     * @return array[]
     * @throws NoSuchEntityException
     */
    public function createShipment($data)
    {
        $orderId       = $data['orderId'];
        $trackingId    = $data['trackingId'];
        $magOrder      = $this->helper->getOrderByDocumentId($orderId);
        $shipmentItems = [];
        if ($magOrder->canShip()) {
            $items    = $this->helper->getItems($magOrder, $data['lines']);
            $shipItem = [];
            foreach ($items as $itemData) {
                $item                        = $itemData['item'];
                $orderItemId                 = $item->getItemId();
                $shipmentItems[$orderItemId] = $itemData['qty'];
            }
            if (count($shipmentItems) > 0) {
                foreach ($shipmentItems as $orderItemId => $qty) {
                    $itemCreation = $this->shipmentItemCreationInterface;
                    $itemCreation->setOrderItemId($orderItemId)->setQty($qty);
                    $shipItem[] = clone $itemCreation;

                }
                $shipmentItem = $this->shipmentInterface->setItems($shipItem);

                $items = [];
                if (count($shipmentItem->getItems()) > 0) {
                    $items = $shipmentItem->getItems();
                }

                $shipmentTracks = $this->trackFactory->create();
                if (!empty($trackingId)) {
                    $shipmentTracks->setCarrierCode($data['shipmentProvider']);
                    $shipmentTracks->setTitle($data['service']);
                    $shipmentTracks->setDescription($data['service']);
                    $shipmentTracks->setTrackNumber($trackingId);
                }

                $this->shipmentCommentCreation->setComment(__("Shipment added from LS Central"))
                    ->setIsVisibleOnFront(0);

                $this->shipOrderInterface->execute(
                    $magOrder->getEntityId(),
                    $items,
                    true,
                    $appendComment = false,
                    $this->shipmentCommentCreation,
                    [$shipmentTracks]
                );
            }
        }

        $shipmentDetails = $this->getShipmentDetailsByOrder($magOrder);

        return $this->helper->outputShipmentMessage(
            true,
            $shipmentDetails
        );
    }

    /** Get shipment details
     * @param $magOrder
     * @return array
     */
    public function getShipmentDetailsByOrder($magOrder)
    {
        $trackDataArray  = [];
        $trackData       = [];
        $shipmentDetails = $magOrder->getTracksCollection();
        foreach ($shipmentDetails->getItems() as $trackInfo) {
            $trackData ['Tracking Id']       = $trackInfo->getTrackNumber();
            $trackData ['Shipment Provider'] = $trackInfo->getCarrierCode();
            $trackData ['Service']           = $trackInfo->getTitle();
            $trackDataArray []               = $trackData;
        }

        return $trackDataArray;
    }
}
