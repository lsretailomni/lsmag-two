<?php

namespace Ls\Webhooks\Model\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use Magento\Shipping\Helper\Data as ShippingHelper;
use \Ls\Webhooks\Helper\Data;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Webhooks\Helper\NotificationHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsExtensionInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterfaceFactory;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterfaceFactory;
use \Ls\Webhooks\Logger\Logger;
use \Ls\Core\Model\LSR;

/**
 * class to create shipment through webhook
 */
class Shipment
{

    /**
     * @param ShipOrderInterface $shipOrderInterface
     * @param ShipmentItemCreationInterface $shipmentItemCreationInterface
     * @param ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory
     * @param ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory
     * @param ReplicationHelper $replicationHelper
     * @param DefaultSourceProviderInterfaceFactory $defaultSourceProviderFactory
     * @param ShipmentInterface $shipmentInterface
     * @param TrackFactory $trackFactory
     * @param ShipmentCommentCreationInterface $shipmentCommentCreation
     * @param Data $helper
     * @param NotificationHelper $notificationHelper
     * @param ShippingHelper $shippingHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Logger $logger
     * @param LSR $lsr
     */
    public function __construct(
        private ShipOrderInterface $shipOrderInterface,
        private ShipmentItemCreationInterface $shipmentItemCreationInterface,
        private ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory,
        private ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory,
        private ReplicationHelper $replicationHelper,
        private DefaultSourceProviderInterfaceFactory $defaultSourceProviderFactory,
        private ShipmentInterface $shipmentInterface,
        private TrackFactory $trackFactory,
        private ShipmentCommentCreationInterface $shipmentCommentCreation,
        private Data $helper,
        private NotificationHelper $notificationHelper,
        private ShippingHelper $shippingHelper,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        private Logger $logger,
        private LSR $lsr
    ) {
    }

    /**
     * Creating shipment in Magento
     * @param $data
     * @return array[]
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function createShipment($data)
    {
        $orderId             = $data['orderId'];
        $trackingId          = $data['trackingId'];
        $lsCentralShippingId = $data['lsCentralShippingId'];
        $lines               = $data['lines'];
        $status              = true;
        $statusMsg           = '';
        $shipmentDetails     = [];
        $magOrder            = $this->helper->getOrderByDocumentId($orderId);
        $storeId             = $magOrder->getStoreId();

        if (!empty($magOrder)) {
            if ($magOrder->canShip()
                && !$this->getShipmentExists($orderId, $lsCentralShippingId)) {
                //if shipment not exists create shipment
                $shipItems   = [];
                $parentItems = [];
                foreach ($magOrder->getAllItems() as $orderItem) {
                    $parentItem = $orderItem->getParentItem();

                    if (!$this->helper->isAllowed($orderItem, $lines) ||
                        ($parentItem ?
                            !$parentItem->getQtyToShip() : !$orderItem->getQtyToShip())
                    ) {
                        continue;
                    }

                    if (empty($parentItem)) {
                        $parentItem = $orderItem;
                    }

                    if (in_array($parentItem->getItemId(), $parentItems)) {
                        continue;
                    }
                    $qty          = $this->helper->getQtyToShip($orderItem, $lines);
                    $itemCreation = $this->shipmentItemCreationInterface;
                    $itemCreation->setOrderItemId($parentItem->getItemId())->setQty($qty);
                    $shipItems[]   = clone $itemCreation;
                    $parentItems[] = $parentItem->getItemId();
                }
                if (!empty($shipItems)) {
                    $shipmentItemArray = $this->shipmentInterface->setItems($shipItems)->getItems();
                    $shipmentTracks    = $this->trackFactory->create();
                    if (!empty($trackingId)) {
                        $shipmentTracks->setCarrierCode($data['shipmentProvider']);
                        $shipmentTracks->setTitle($data['service']);
                        $shipmentTracks->setDescription($data['service']);
                        $shipmentTracks->setTrackNumber($trackingId);
                    }
                    $shipmentTracks = [$shipmentTracks];
                    
                    if ($this->lsr->getWebsiteConfig(
                        LSR::LSR_SHIPMENT_WITHOUT_TRACKING,
                        $magOrder->getStore()->getWebsiteId()
                    )
                    ) {
                        $shipmentTracks = [];
                    }

                    $this->shipmentCommentCreation->setComment(__("Shipment added from LS Central"))
                        ->setIsVisibleOnFront(0);

                    $defaultSourceCode = $this->defaultSourceProviderFactory->create()->getCode();
                    $websiteId         = $magOrder->getStore()->getWebsiteId();
                    $sourceCode        = $this->replicationHelper->getSourceCodeFromWebsiteCode(
                        $defaultSourceCode,
                        $websiteId
                    );

                    $arguments = $this->shipmentArgumentsFactory->create();
                    $extension = $this->argumentExtensionFactory
                        ->create()
                        ->setSourceCode($sourceCode);
                    $arguments->setExtensionAttributes($extension);

                    $shipmentId = $this->shipOrderInterface->execute(
                        $magOrder->getEntityId(),
                        $shipmentItemArray,
                        true,
                        false,
                        $this->shipmentCommentCreation,
                        $shipmentTracks,
                        [],
                        $arguments
                    );

                    $shipmentDetails = $this->getShipmentDetailsByOrder($magOrder, $shipmentId, $lsCentralShippingId);
                    $statusMsg       = "Your order has been shipped for order# $orderId";
                }
            } else { //if shipment exists update tracking number

                $status    = $this->updateTrackingId($orderId, $lsCentralShippingId, $trackingId);
                $statusMsg = ($status) ? "Tracking Id updated successfully." : "Tracking Id update failed";

            }
            if ($magOrder->getShippingMethod() != "clickandcollect_clickandcollect" && $status) {
                $items = $this->helper->getItems($magOrder, $lines, false);
                $this->notificationHelper->processNotifications(
                    $storeId,
                    $magOrder,
                    $items,
                    $statusMsg,
                    ''
                );
            }
        }

        return $this->helper->outputShipmentMessage(
            $status,
            $statusMsg,
            $shipmentDetails
        );
    }

    /**
     * Get Shipment exists status based on shipment Id from Central
     *
     * @param $orderId
     * @param $shipmentId
     * @return bool
     */
    public function getShipmentExists($orderId, $shipmentId)
    {
        $magOrder           = $this->helper->getOrderByDocumentId($orderId);
        $shipmentCollection = $magOrder->getTracksCollection();
        $shipmentExists     = false;
        foreach ($shipmentCollection->getItems() as $shipment) {
            if ($shipment->getShipmentId() == $shipmentId) {
                $shipmentExists = true;
            }
        }

        return $shipmentExists;
    }

    /**
     * Update central tracking Id
     *
     * @param $orderId
     * @param $lsCentralShippingId
     * @param $trackingId
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function updateTrackingId($orderId, $lsCentralShippingId, $trackingId)
    {
        $magOrder           = $this->helper->getOrderByDocumentId($orderId);
        $shipmentCollection = $magOrder->getTracksCollection();
        $status             = false;
        try {
            foreach ($shipmentCollection->getItems() as $shipment) {

                if ($shipment->getLsCentralShippingId() == $lsCentralShippingId) {
                    $shipment->setTrackNumber($trackingId);
                    $shipment->save();
                    $status = true;
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Could not update Tracking Id, see error log for details')
            );
        }
        return $status;
    }

    /**
     * Get Shipment details by order
     *
     * @param $magOrder
     * @param $shipmentId
     * @param $lsCentralShippingId
     * @return array
     * @throws CouldNotSaveException
     */
    public function getShipmentDetailsByOrder($magOrder, $shipmentId, $lsCentralShippingId)
    {
        $trackDataArray  = [];
        $trackData       = [];
        $shipmentDetails = $magOrder->getTracksCollection();
        try {
            foreach ($shipmentDetails->getItems() as $trackInfo) {
                if ($shipmentId == $trackInfo->getParentId()) {
                    $trackData ['trackingId']       = $trackInfo->getTrackNumber();
                    $trackData ['trackingUrl']      = $this->shippingHelper->getTrackingPopupUrlBySalesModel($magOrder);
                    $trackData ['shipmentProvider'] = $trackInfo->getCarrierCode();
                    $trackData ['service']          = $trackInfo->getTitle();

                    //Sync LS central shipping Id to shipment track
                    $trackInfo->setLsCentralShippingId($lsCentralShippingId);
                    $trackInfo->save();
                }
                $trackDataArray [] = $trackData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Could not update LS Sentral Shipping Id, see error log for details')
            );
        }

        return $trackDataArray;
    }
}
