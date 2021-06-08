<?php

namespace Ls\Webhooks\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryLine;
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
     * Match sales entry line with line number
     * @param $salesEntry
     * @param $lines
     * @return array
     */
    public function matchLineNumberWithSalesEntry($salesEntry, $lines)
    {
        $itemInfoArray = null;
        $count         = 0;
        if (!empty($salesEntry)) {
            /** @var SalesEntryLine $salesEntryLine */
            foreach ($salesEntry->getLines() as $salesEntryLine) {
                if ($salesEntryLine->getItemId() != $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID)) {
                    $key = array_search($salesEntryLine->getLineNumber(), array_column($lines, 'LineNo'));
                    if ($key !== false) {
                        $statusKey                                      = $lines[$key]['NewStatus'];
                        $itemInfoArray[$statusKey][$count]['itemId']    = $salesEntryLine->getItemId();
                        $itemInfoArray[$statusKey][$count]['qty']       = $salesEntryLine->getQuantity();
                        $itemInfoArray[$statusKey][$count]['uom']       = $salesEntryLine->getUomId();
                        $itemInfoArray[$statusKey][$count]['variantId'] = $salesEntryLine->getVariantId();
                        $count++;
                    }
                }

            }
        }

        return $itemInfoArray;
    }

    /**
     * Get sales entry
     * @param $documentId
     * @return SalesEntry|\Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetResponse|\Ls\Omni\Client\ResponseInterface|null
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function getSalesEntry($documentId)
    {
        return $this->orderHelper->getOrderDetailsAgainstId($documentId);
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
     * @return mixed
     */
    public function isCollectedNotifyEnabled($storeId)
    {
        return $this->lsr->getStoreConfig(LSR::LS_NOTIFICATION_COLLECTED, $storeId);
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
     * Get store name by store id
     * @param $storeId
     * @return mixed|string
     */
    public function getStoreName($storeId)
    {
        return $this->omniHelper->getStoreNameById($storeId);
    }

    /**
     * Get product values for item
     * @param $pickupStoreId
     * @return mixed|string
     * @throws NoSuchEntityException
     */
    public function getComparisonValues($item)
    {
        return $this->itemHelper->getComparisonValues($item);
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
            list($itemId, $variantId, $uom) = $this->getComparisonValues($orderItem);
            foreach ($itemsInfo as $skuValues) {
                if ($itemId == $skuValues['itemId'] && $uom == $skuValues['uom'] &&
                    $variantId == $skuValues['variantId']) {
                    $items[] = $orderItem;
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
     * @return void
     */
    public function updateOrderStatus($order, $state, $status)
    {
        $order->setState($state)->setStatus($status);
    }
}
