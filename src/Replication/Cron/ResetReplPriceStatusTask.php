<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Cron responsible to reset the relevant counters to do replication again
 */
class ResetReplPriceStatusTask
{
    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_price_status_reset';

    /**
     * @var string
     */
    public $defaultScope = ScopeInterface::SCOPE_WEBSITES;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /** @var Logger */
    public $logger;

    /**
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param Logger $logger
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        Logger $logger
    ) {
        $this->replicationHelper = $replicationHelper;
        $this->lsr = $LSR;
        $this->logger = $logger;
        $this->setDefaultScope();
    }

    /**
     * Entry point for cron jobs
     *
     * @param null $storeData
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
        if (!$this->lsr->isSSM()) {
            if (!empty($storeData) && $storeData instanceof WebsiteInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->lsr->getAllWebsites();
            }
        } else {
            $stores = [$this->lsr->getAdminStore()];
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                if ($this->lsr->isLSR($store->getId(), $this->defaultScope)) {
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        self::CONFIG_PATH_LAST_EXECUTE,
                        $store->getId(),
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommPricesTask::CONFIG_PATH_STATUS,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommPricesTask::CONFIG_PATH,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommPricesTask::CONFIG_PATH_MAX_KEY,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                }
            }
        }
    }

    /**
     * Entry point for manually run cron jobs
     *
     * @param null $storeData
     * @return array
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        return [0];
    }

    /**
     * Set default scope
     *
     */
    public function setDefaultScope()
    {
        if ($this->lsr->isSSM()) {
            $this->defaultScope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
    }
}
