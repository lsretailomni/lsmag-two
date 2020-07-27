<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class SyncOrders
 * @package Ls\Replication\Cron
 */
class SyncOrders
{

    /**
     * @var ReplicationHelper
     */
    public $replicationHelper;

    /**
     * @var Data
     */
    public $helper;

    /**
     * @var LSR
     */
    public $lsr;

    /** @var StoreInterface $store */
    public $store;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @var MessageInterface
     */
    public $messageInterface;

    /**
     * SyncOrders constructor.
     * @param LSR $lsr
     * @param Data $helper
     * @param ReplicationHelper $replicationHelper
     * @param OrderHelper $orderHelper
     * @param ManagerInterface $eventManager
     * @param CartRepositoryInterface $cartRepository
     * @param MessageInterface $messageInterface
     */
    public function __construct(
        LSR $lsr,
        Data $helper,
        ReplicationHelper $replicationHelper,
        OrderHelper $orderHelper,
        ManagerInterface $eventManager,
        CartRepositoryInterface $cartRepository,
        MessageInterface $messageInterface
    ) {

        $this->lsr               = $lsr;
        $this->helper            = $helper;
        $this->replicationHelper = $replicationHelper;
        $this->orderHelper       = $orderHelper;
        $this->eventManager      = $eventManager;
        $this->cartRepository    = $cartRepository;
        $this->messageInterface  = $messageInterface;
    }

    /**
     * @param null $storeData
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
        $info = [];
        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            /** @var StoreInterface[] $stores */
            $stores = $this->lsr->getAllStores();
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $orders = $this->orderHelper->getOrders($this->store->getId());
                    if (!empty($orders)) {
                        foreach ($orders as $order) {
                            $quote = $this->cartRepository->get($order->getQuoteId());
                            $this->eventManager->dispatch(
                                'sales_model_service_quote_submit_before',
                                [
                                    'order' => $order,
                                    'quote' => $quote
                                ]
                            );
                            $this->eventManager->dispatch('sales_order_place_after', ['order' => $order]);
                            $this->messageInterface->getMessages(true);
                        }

                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::SC_CRON_SYNC_ORDERS_CONFIG_PATH_LAST_EXECUTE,
                            $this->store->getId()
                        );
                    }

                    $info[] = -1;
                    return $info;
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * @param null $storeData
     * @return array
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $info = $this->execute($storeData);
        return $info;
    }
}
