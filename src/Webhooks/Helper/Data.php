<?php

namespace Ls\Webhooks\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Webhooks\Logger\Logger;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\Data as OmniHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Helper class to handle webhooks function
 */
class Data
{

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var OmniHelper
     */
    public $omniHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * Data constructor.
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderHelper $orderHelper
     * @param LSR $lsr
     * @param OmniHelper $omniHelper
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderHelper $orderHelper,
        LSR $lsr,
        OmniHelper $omniHelper,
        ItemHelper $itemHelper
    ) {

        $this->logger                = $logger;
        $this->orderRepository       = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderHelper           = $orderHelper;
        $this->lsr                   = $lsr;
        $this->omniHelper            = $omniHelper;
        $this->itemHelper            = $itemHelper;
    }

    /**
     * Get order by document Id
     * @param $documentId
     * @return array|OrderInterface|OrderInterface[]
     */
    public function getOrderByDocumentId($documentId)
    {
        try {
            $order = [];
            $order = $this->orderRepository->getList(
                $this->searchCriteriaBuilder->addFilter('document_id', $documentId, 'eq')->create()
            )->getItems();
            foreach ($order as $ord) {
                return $ord;
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $order;
    }

    /**
     * Map item lines with status
     *
     * @param $lines
     * @return array
     */
    public function mapStatusWithItemLines($lines)
    {
        $itemInfoArray = null;
        $count         = 0;
        if (!empty($lines)) {
            foreach ($lines as $line) {
                if ($line['ItemId'] != $this->getShippingItemId()) {
                        $statusKey                                            = $line['NewStatus'];
                        $itemInfoArray[$statusKey][$count]['ItemId']          = $line['ItemId'];
                        $itemInfoArray[$statusKey][$count]['Quantity']        = $line['Quantity'];
                        $itemInfoArray[$statusKey][$count]['UnitOfMeasureId'] = $line['UnitOfMeasureId'];
                        $itemInfoArray[$statusKey][$count]['VariantId']       = $line['VariantId'];
                        $count++;
                }

            }
        }

        return $itemInfoArray;
    }

    /**
     * Getting store email
     * @param $storeId
     * @return string
     */
    public function getStoreEmail($storeId)
    {
        return $this->lsr->getStoreConfig('trans_email/ident_general/email', $storeId);
    }

    /**
     * Get configuration for pickup email
     * @param $storeId
     * @return string
     */
    public function isPickupNotifyEnabled($storeId)
    {
        return $this->lsr->getStoreConfig(LSR::LS_NOTIFICATION_PICKUP, $storeId);
    }

    /**
     * Get configuration for collected email
     * @param $storeId
     * @return string
     */
    public function isCollectedNotifyEnabled($storeId)
    {
        return $this->lsr->getStoreConfig(LSR::LS_NOTIFICATION_COLLECTED, $storeId);
    }

    /**
     * Get configuration for collected email
     * @param $storeId
     * @return string
     */
    public function isCancelNotifyEnabled($storeId)
    {
        return $this->lsr->getStoreConfig(LSR::LS_NOTIFICATION_CANCEL, $storeId);
    }

    /**
     * Get configuration for pickup email template
     * @param $storeId
     * @return string
     */
    public function getPickupTemplate($storeId)
    {
        return $this->lsr->getStoreConfig(LSR::LS_NOTIFICATION_EMAIL_TEMPLATE_PICKUP, $storeId);
    }

    /**
     * Get configuration for collected email template
     * @param $storeId
     * @return string
     */
    public function getCollectedTemplate($storeId)
    {
        return $this->lsr->getStoreConfig(LSR::LS_NOTIFICATION_EMAIL_TEMPLATE_COLLECTED, $storeId);
    }

    /**
     * Get configuration for collected email template
     * @param $storeId
     * @return string
     */
    public function getCancelTemplate($storeId)
    {
        return $this->lsr->getStoreConfig(LSR::LS_NOTIFICATION_EMAIL_TEMPLATE_CANCEL, $storeId);
    }

    /**
     * Get store name by store id
     * @param $storeId
     * @return mixed|string
     */
    public function getStoreName($storeId)
    {
        return $this->omniHelper->getStoreNameById($storeId);
    }

    /**
     * Get items detail
     * @param $order
     * @param $itemsInfo
     * @return array
     * @throws NoSuchEntityException
     */
    public function getItems($order, $itemsInfo)
    {
        $items = [];
        foreach ($order->getAllVisibleItems() as $orderItem) {
            list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                $orderItem->getProductId(),
                $orderItem->getSku()
            );
            foreach ($itemsInfo as $skuValues) {
                if ($itemId == $skuValues['ItemId'] && $uom == $skuValues['UnitOfMeasureId'] &&
                    $variantId == $skuValues['VariantId'] && $itemId != $this->getShippingItemId()) {
                    $items[$itemId]['item'] = $orderItem;
                    $items[$itemId]['qty']  = $skuValues['Quantity'];
                    if (array_key_exists('Amount', $skuValues)) {
                        $items[$itemId]['amount'] = $skuValues['Amount'];
                    }
                }
            }
        }
        return $items;
    }

    /**
     * For returning order repository object
     * @return OrderRepositoryInterface
     */
    public function getOrderRepository()
    {
        return $this->orderRepository;
    }

    /**
     * Set status and return order
     * @param $order
     * @param $state
     * @param $status
     * @return void
     */
    public function updateOrderStatus($order, $state, $status)
    {
        $order->setState($state)->setStatus($status);
    }

    /**
     * Return shipping Id
     *
     * @return array|string
     */
    public function getShippingItemId()
    {
        return $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID);
    }

    /**
     * Check is click and collect order
     *
     * @param $magOrder
     * @return bool
     */
    public function isClickAndcollectOrder($magOrder)
    {
        return $magOrder->getShippingMethod() == 'clickandcollect_clickandcollect';
    }

    /**
     * Return message to Ls Central
     * @param $status
     * @param $message
     * @return array[]
     */
    public function outputMessage($status, $message)
    {
        return [
            "data" => [
                'success' => $status,
                'message' => __($message)
            ]
        ];
    }

    /**
     * Return message to Ls Central
     * @param $status
     * @param $message
     * @return array[]
     */
    public function outputShipmentMessage($status, $message)
    {
        return [
            "data" => [
                'success'      => $status,
                'trackingInfo' => $message
            ]
        ];
    }
}
