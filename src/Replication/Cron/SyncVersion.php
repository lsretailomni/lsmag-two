<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Cron responsible to check version of commerce service and save necessary values
 */
class SyncVersion
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
     * SyncVersion constructor.
     * @param LSR $lsr
     * @param Data $helper
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        LSR $lsr,
        Data $helper,
        ReplicationHelper $replicationHelper
    ) {

        $this->lsr               = $lsr;
        $this->helper            = $helper;
        $this->replicationHelper = $replicationHelper;
    }

    /**
     * Entry point for cron
     *
     * @param $storeData
     * @return array|void
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
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
                    $info = [];
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_VERSION_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );
                    $baseUrl = $this->lsr->getStoreConfig(LSR::SC_SERVICE_BASE_URL, $this->store->getId());
                    $lsKey   = $this->lsr->getStoreConfig(LSR::SC_SERVICE_LS_KEY, $this->store->getId());
                    $pong    = $this->helper->omniPing($baseUrl, $lsKey);
                    $this->helper->parsePingResponseAndSaveToConfigData($pong);
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
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $info = $this->execute($storeData);
        return $info;
    }
}
