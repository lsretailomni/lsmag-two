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
use Magento\Sales\Api\ShipmentRepositoryInterface;
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
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var TrackFactory
     */
    private $trackFactory;

    /**
     * @var ShipmentCommentCreationInterface
     */
    private $shipmentCommentCreation;

    /**
     * @var ShippingHelper
     */
    private $shippingHelper;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ShipmentCreationArgumentsInterfaceFactory
     */
    private $shipmentArgumentsFactory;

    /**
     * @var ShipmentCreationArgumentsExtensionInterfaceFactory
     */
    private $argumentExtensionFactory;

    /**
     * @var ReplicationHelper
     */
    private $replicationHelper;

    /**
     * @var DefaultSourceProviderInterfaceFactory
     */
    private $defaultSourceProviderFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ShipOrderInterface $shipOrderInterface
     * @param ShipmentItemCreationInterface $shipmentItemCreationInterface
     * @param ShipmentCreationArgumentsInterfaceFactory $shipmentCreationArgumentsInterfaceFactory
     * @param ShipmentCreationArgumentsExtensionInterfaceFactory $shipmentCreationArgumentsExtensionInterfaceFactory
     * @param ReplicationHelper $replicationHelper
     * @param DefaultSourceProviderInterfaceFactory $defaultSourceProviderFactory
     * @param ShipmentInterface $shipmentInterface
     * @param TrackFactory $trackFactory
     * @param ShipmentCommentCreationInterface $shipmentCommentCreation
     * @param Data $helper
     * @param NotificationHelper $notificationHelper
     * @param ShippingHelper $shippingHelper
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Logger $logger
     */
    public function __construct(
        ShipOrderInterface $shipOrderInterface,
        ShipmentItemCreationInterface $shipmentItemCreationInterface,
        ShipmentCreationArgumentsInterfaceFactory $shipmentCreationArgumentsInterfaceFactory,
        ShipmentCreationArgumentsExtensionInterfaceFactory $shipmentCreationArgumentsExtensionInterfaceFactory,
        ReplicationHelper $replicationHelper,
        DefaultSourceProviderInterfaceFactory $defaultSourceProviderFactory,
        ShipmentInterface $shipmentInterface,
        TrackFactory $trackFactory,
        ShipmentCommentCreationInterface $shipmentCommentCreation,
        Data $helper,
        NotificationHelper $notificationHelper,
        ShippingHelper $shippingHelper,
        ShipmentRepositoryInterface $shipmentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger
    ) {
        $this->shipOrderInterface            = $shipOrderInterface;
        $this->shipmentItemCreationInterface = $shipmentItemCreationInterface;
        $this->shipmentArgumentsFactory      = $shipmentCreationArgumentsInterfaceFactory;
        $this->argumentExtensionFactory      = $shipmentCreationArgumentsExtensionInterfaceFactory;
        $this->replicationHelper             = $replicationHelper;
        $this->defaultSourceProviderFactory  = $defaultSourceProviderFactory;
        $this->shipmentInterface             = $shipmentInterface;
        $this->trackFactory                  = $trackFactory;
        $this->shipmentCommentCreation       = $shipmentCommentCreation;
        $this->helper                        = $helper;
        $this->notificationHelper            = $notificationHelper;
        $this->shippingHelper                = $shippingHelper;
        $this->shipmentRepository            = $shipmentRepository;
        $this->searchCriteriaBuilder         = $searchCriteriaBuilder;
        $this->logger                        = $logger;
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
                        [$shipmentTracks],
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

            if ($status) {
                $items = $this->helper->getItems($magOrder, $lines, false);
                $this->notificationHelper->processNotifications(
                    $storeId,
                    $magOrder,
                    $items,
                    $statusMsg
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
