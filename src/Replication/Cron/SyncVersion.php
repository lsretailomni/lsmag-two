<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Replication\Helper\ReplicationHelper;

/**
 * Class SyncVersion
 * @package Ls\Replication\Cron
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


    public function execute()
    {
        if ($this->lsr->isLSR()) {
            $info = [];
            $this->replicationHelper->updateConfigValue(
                $this->replicationHelper->getDateTime(),
                LSR::SC_VERSION_CONFIG_PATH_LAST_EXECUTE
            );
            $baseUrl = $this->lsr->getStoreConfig(LSR::SC_SERVICE_BASE_URL);
            $lsKey   = $this->lsr->getStoreConfig(LSR::SC_SERVICE_LS_KEY);
            $pong    = $this->helper->omniPing($baseUrl, $lsKey);
            $this->helper->parsePingResponseAndSaveToConfigData($pong);
            $info[] = -1;
            return $info;
        }
    }

    public function executeManually()
    {
        $info = $this->execute();
        return $info;
    }
}
