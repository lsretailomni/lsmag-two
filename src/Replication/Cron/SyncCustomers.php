<?php

namespace Ls\Replication\Cron;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Cron responsible for syncing customer in magento
 */
class SyncCustomers
{
    /** @var StoreInterface $store */
    public $store;

    /**
     * @param LSR $lsr
     * @param Data $helper
     * @param ReplicationHelper $replicationHelper
     * @param ContactHelper $contactHelper
     * @param ManagerInterface $eventManager
     * @param CartRepositoryInterface $cartRepository
     * @param StoreManagerInterface $storeManager
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        public LSR $lsr,
        public Data $helper,
        public ReplicationHelper $replicationHelper,
        public ContactHelper $contactHelper,
        public ManagerInterface $eventManager,
        public CartRepositoryInterface $cartRepository,
        public StoreManagerInterface $storeManager,
        public CustomerRegistry $customerRegistry
    ) {
    }

    /**
     * Entry point for cron
     *
     * @param $storeData
     * @return array|void
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function execute($storeData = null)
    {
        $info = [];

        if (!$this->lsr->isSSM()) {
            if (!empty($storeData) && $storeData instanceof StoreInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->lsr->getAllStores();
            }
        } else {
            $stores = [$this->lsr->getAdminStore()];
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $customers = $this->contactHelper->getAllCustomers(
                        !$this->lsr->isSSM() ?
                        $this->store->getWebsiteId() :
                            $this->storeManager->getDefaultStoreView()->getWebsiteId()
                    );
                    if (!empty($customers)) {
                        foreach ($customers as $customer) {
                            $customer = $this->customerRegistry->retrieve($customer->getId());
                            $this->contactHelper->syncCustomerAndAddress($customer);
                        }
                    }

                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_CRON_SYNC_CUSTOMERS_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );
                }
                $this->lsr->setStoreId(null);
            }

            $info[] = -1;

            return $info;
        }
    }

    /**
     * Execute manually
     *
     * @param $storeData
     * @return array|null
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function executeManually($storeData = null)
    {
        $info = $this->execute($storeData);
        return $info;
    }
}
