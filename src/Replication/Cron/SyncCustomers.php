<?php

namespace Ls\Replication\Cron;

use Ls\Core\Model\LSR;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Helper\Data;
use Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class SyncOrders
 * @package Ls\Replication\Cron
 */
class SyncCustomers
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
     * @var ContactHelper
     */
    public $contactHelper;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * SyncVersion constructor.
     * @param LSR $lsr
     * @param Data $helper
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        LSR $lsr,
        Data $helper,
        ReplicationHelper $replicationHelper,
        ContactHelper $contactHelper,
        ManagerInterface $eventManager,
        CartRepositoryInterface $cartRepository
    ) {

        $this->lsr               = $lsr;
        $this->helper            = $helper;
        $this->replicationHelper = $replicationHelper;
        $this->contactHelper     = $contactHelper;
        $this->eventManager      = $eventManager;
        $this->cartRepository    = $cartRepository;
    }

    /**
     * @param null $storeData
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
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
                    $customers = $this->contactHelper->getAllCustomers($this->store->getWebsiteId());
                    if (!empty($customers)) {
                        foreach ($customers as $customer) {

                        }

                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::SC_CRON_SYNC_CUSTOMERS_CONFIG_PATH_LAST_EXECUTE,
                            $this->store->getId()
                        );
                    }
                }
                $this->lsr->setStoreId(null);
            }
        }
    }
}
