<?php

namespace Ls\CommerceCloud\Plugin\Cron;

use \Ls\CommerceCloud\Model\LSR;
use \Ls\Replication\Cron\AbstractReplicationTask;

/**
 * Interceptor to intercept AbstractReplicationTask
 */
class AbstractReplicationTaskPlugin
{
    /**
     * After plugin to set the respective app_id and full_replication
     *
     * @param AbstractReplicationTask $subject
     * @param $result
     * @param $lsr
     * @param $storeId
     * @return array
     */
    public function afterGetRequiredParamsForMakingRequest(AbstractReplicationTask $subject, $result, $lsr, $storeId)
    {
        $appId = $lsr->getStoreConfig(LSR::SC_REPLICATION_LS_APP_ID, $storeId);

        if ($appId) {
            $result[6] = $appId;
            $result[1] = 0;
        }

        return $result;
    }

}
