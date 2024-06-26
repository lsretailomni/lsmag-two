<?php

namespace Ls\Webhooks\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\GetPointRateResponse;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetResponse;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetSalesByOrderIdResponse;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Webhooks\Logger\Logger;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\Data as OmniHelper;
use \Ls\Webhooks\Model\Notification\EmailNotification;
use \Ls\Webhooks\Model\Notification\PushNotification;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;

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
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var SerializerJson
     */
    public $jsonSerializer;

    /**
     * @var PushNotification
     */
    public $pushNotification;

    /**
     * @var EmailNotification
     */
    public $emailNotification;

    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderHelper $orderHelper
     * @param LSR $lsr
     * @param OmniHelper $omniHelper
     * @param ItemHelper $itemHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param SerializerJson $jsonSerializer
     * @param ProductRepository $productRepository
     * @param PushNotification $pushNotification
     * @param EmailNotification $emailNotification
     */
    public function __construct(
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderHelper $orderHelper,
        LSR $lsr,
        OmniHelper $omniHelper,
        ItemHelper $itemHelper,
        LoyaltyHelper $loyaltyHelper,
        SerializerJson $jsonSerializer,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        PushNotification $pushNotification,
        EmailNotification $emailNotification
    ) {

        $this->logger                = $logger;
        $this->orderRepository       = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderHelper           = $orderHelper;
        $this->lsr                   = $lsr;
        $this->omniHelper            = $omniHelper;
        $this->itemHelper            = $itemHelper;
        $this->loyaltyHelper         = $loyaltyHelper;
        $this->jsonSerializer        = $jsonSerializer;
        $this->productRepository     = $productRepository;
        $this->pushNotification      = $pushNotification;
        $this->emailNotification     = $emailNotification;
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
                $statusKey                                            = $line['NewStatus'];
                $itemInfoArray[$statusKey][$count]['ItemId']          = $line['ItemId'];
                $itemInfoArray[$statusKey][$count]['Quantity']        = $line['Quantity'];
                $itemInfoArray[$statusKey][$count]['UnitOfMeasureId'] = $line['UnitOfMeasureId'];
                $itemInfoArray[$statusKey][$count]['VariantId']       = $line['VariantId'];
                $itemInfoArray[$statusKey][$count]['Amount']          = $line['Amount'];
                $count++;
            }
        }

        return $itemInfoArray;
    }

    /**
     * Getting sender name
     * @param $storeId
     * @return string
     */
    public function getSenderName($storeId)
    {
        return $this->lsr->getStoreConfig('trans_email/ident_general/name', $storeId);
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
     * Get configuration for notification type
     *
     * @param mixed $storeId
     * @return string
     */
    public function getNotificationType($storeId = null)
    {
        return $this->lsr->getNotificationType($storeId);
    }

    /**
     * Is notification enabled
     *
     * @param string $notificationType
     * @param string $orderStatus
     * @param string $storeId
     * @return bool
     */
    public function isNotifyEnabled($notificationType, $orderStatus, $storeId)
    {
        $enabled = false;

        if ($notificationType == LSR::LS_NOTIFICATION_EMAIL) {
            $config = $this->lsr->getStoreConfig(LSR::LS_EMAIL_NOTIFICATION_ORDER_STATUS, $storeId);

            if (!is_array($config)) {
                $config = $this->jsonSerializer->unserialize($config);
            }

            foreach ($config as $item) {
                if ($item['order_status'] == $orderStatus) {
                    $enabled = true;
                    break;
                }
            }
        }

        return $enabled;
    }

    /**
     * Is notification enabled
     *
     * @param string $notificationType
     * @param string $orderStatus
     * @param string $storeId
     * @return bool
     */
    public function getNotificationTemplate($notificationType, $orderStatus, $storeId)
    {
        $template = 'ls_mag_webhooks_template_misc';

        if ($notificationType == LSR::LS_NOTIFICATION_EMAIL) {
            $config = $this->lsr->getStoreConfig(LSR::LS_EMAIL_NOTIFICATION_ORDER_STATUS, $storeId);

            if (!is_array($config)) {
                $config = $this->jsonSerializer->unserialize($config);
            }

            foreach ($config as $item) {
                if ($item['order_status'] == $orderStatus) {
                    $template = $item['email_template'];
                    break;
                }
            }
        }

        return $template;
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
     * @param bool $linesMerged
     * @return array
     * @throws NoSuchEntityException
     */
    public function getItems($order, $itemsInfo, $linesMerged = true)
    {
        $items                = [];
        $globalCounter        = 0;
        $giftCardItemsCounter = 0;
        foreach ($order->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getProductType() == Type::TYPE_BUNDLE) {
                $children = $orderItem->getChildrenItems();
            } else {
                $children = [$orderItem];
            }

            foreach ($children as $child) {
                list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                    $child->getSku()
                );
                $totalAmount = 0;
                $counter     = 0;
                foreach ($itemsInfo as $index => $skuValues) {
                    if ($itemId == $skuValues['ItemId'] && $uom == $skuValues['UnitOfMeasureId'] &&
                        $variantId == $skuValues['VariantId'] && $itemId != $this->getShippingItemId()) {
                        if (in_array($skuValues['ItemId'], explode(',', $this->getGiftCardIdentifiers()))
                        ) {
                            if ($giftCardItemsCounter < $this->getGiftCardOrderItemsQty($order) &&
                                $orderItem->getQtyOrdered() - $orderItem->getQtyInvoiced() > 0
                            ) {
                                $items[$globalCounter][$itemId]['itemStatus'] = $child->getStatusId();
                                $items[$globalCounter][$itemId]['qty']        = (float)$orderItem->getQtyOrdered();
                                $items[$globalCounter][$itemId]['amount']     = $orderItem->getPrice();
                                $items[$globalCounter][$itemId]['item']       = $child;
                                $giftCardItemsCounter++;

                                if (!$linesMerged) {
                                    unset($itemsInfo[$index]);
                                }
                            }
                            break;
                        }

                        if ($counter >= $orderItem->getQtyOrdered()) {
                            continue;
                        }
                        $items[$globalCounter][$itemId]['item'] = $child;
                        if (isset($items[$globalCounter][$itemId]['qty'])) {
                            $items[$globalCounter][$itemId]['qty'] += $skuValues['Quantity'];
                        } else {
                            $items[$globalCounter][$itemId]['qty'] = $skuValues['Quantity'];
                        }
                        if (array_key_exists('Amount', $skuValues)) {
                            $totalAmount                              += $skuValues['Amount'];
                            $items[$globalCounter][$itemId]['amount'] = $totalAmount;
                        }
                        $items[$globalCounter][$itemId]['itemStatus'] = $child->getStatusId();
                        $counter++;
                        unset($itemsInfo[$index]);
                    }
                }
            }
            $globalCounter++;
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
                'message' => $message
            ]
        ];
    }

    /**
     * Return message to Ls Central
     * @param $status
     * @param $statusMsg
     * @param $trackingInfo
     * @return array[]
     */

    public function outputShipmentMessage($status, $statusMsg, $trackingInfo)
    {

        return [
            "data" => [
                'success'      => $status,
                'message'      => $statusMsg,
                'trackingInfo' => $trackingInfo
            ]
        ];
    }

    /**
     * Get point rate
     *
     * @return float|GetPointRateResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getPointRate()
    {
        return $this->loyaltyHelper->getPointRate();
    }

    /**
     * Get product by id
     *
     * @param $id
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProductById($id)
    {
        return $this->productRepository->getById($id);
    }

    /**
     * Is allowed
     *
     * @param $orderItem
     * @param $lines
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isAllowed($orderItem, $lines)
    {
        $product = $this->getProductById($orderItem->getProductId());
        $found   = false;

        foreach ($lines as $line) {
            $itemId    = $line['ItemId'];
            $variantId = $line['VariantId'];

            if ($product->getLsrItemId() == $itemId &&
                $product->getLsrVariantId() == $variantId &&
                $line['Quantity'] > 0) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * Get qty to ship
     *
     * @param $orderItem
     * @param $lines
     * @return int
     * @throws NoSuchEntityException
     */
    public function getQtyToShip($orderItem, &$lines)
    {
        $product = $this->getProductById($orderItem->getProductId());
        $qty     = 0;

        foreach ($lines as $index => $line) {
            $itemId    = $line['ItemId'];
            $variantId = $line['VariantId'];

            if ($product->getLsrItemId() == $itemId &&
                $product->getLsrVariantId() == $variantId
            ) {
                $qty += $line['Quantity'];
                unset($lines[$index]);
            }
        }

        return $qty;
    }

    /**
     * Get gift card identifiers
     *
     * @return array|string
     * @throws NoSuchEntityException
     */
    public function getGiftCardIdentifiers()
    {
        return $this->lsr->getGiftCardIdentifiers();
    }

    /**
     * @param $orderId
     * @return SalesEntry|SalesEntry[]|SalesEntryGetResponse|SalesEntryGetSalesByOrderIdResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     * @throws InvalidEnumException
     */
    public function fetchOrder($orderId)
    {
        return $this->orderHelper->fetchOrder($orderId, DocumentIdType::ORDER);
    }

    /**
     * Return only gift card items from order
     *
     * @param $order
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGiftCardOrderItems($order): array
    {
        $items = [];

        foreach ($order->getAllItems() as $orderItem) {
            if (in_array(
                $orderItem->getProduct()->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE),
                explode(',', $this->getGiftCardIdentifiers())
            )) {
                $items[] = $orderItem;
            }
        }

        return $items;
    }

    /**
     * Return only gift card items qty from order
     *
     * @param $order
     * @return int
     * @throws NoSuchEntityException
     */
    public function getGiftCardOrderItemsQty($order)
    {
        $qty = 0;

        foreach ($this->getGiftCardOrderItems($order) as $orderItem) {
            if (in_array(
                $orderItem->getProduct()->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE),
                explode(',', $this->getGiftCardIdentifiers())
            )) {
                $qty += $orderItem->getQtyOrdered();
            }
        }

        return $qty;
    }

    /**
     *  Process notifications
     *
     * @param int $storeId
     * @param \Magento\Sales\Api\Data\OrderInterface $magOrder
     * @param array $items
     * @param string $statusMsg
     * @param string $type
     * @return void
     * @throws NoSuchEntityException
     */
    public function processNotifications($storeId, $magOrder, $items, $statusMsg, $type = 'All'): void
    {
        $configuredNotificationType = explode(',', $this->getNotificationType($storeId));
        foreach ($configuredNotificationType as $type) {
            if ($type == 'All' || $type == LSR::LS_NOTIFICATION_EMAIL) {
                $this->emailNotification->setNotificationType($statusMsg);
                $this->emailNotification->setOrder($magOrder)->setItems($items);
                $this->emailNotification->prepareAndSendNotification();
            }

            if ($type == 'All' || $type == LSR::LS_NOTIFICATION_PUSH_NOTIFICATION) {
                $this->pushNotification->setNotificationType($statusMsg);
                $this->pushNotification->setOrder($magOrder)->setItems($items);
                $this->pushNotification->prepareAndSendNotification();
            }
        }
    }
}
