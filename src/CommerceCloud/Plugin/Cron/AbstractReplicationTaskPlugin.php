<?php

namespace Ls\CommerceCloud\Plugin\Cron;

use Exception;
use \Ls\CommerceCloud\Model\LSR;
use \Ls\Replication\Cron\AbstractReplicationTask;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Ramsey\Uuid\Uuid;

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
     * @return mixed
     * @throws Exception
     */
    public function afterGetRequiredParamsForMakingRequest(AbstractReplicationTask $subject, $result, $lsr, $storeId)
    {
        $centralType = $lsr->getStoreConfig(LSR::SC_REPLICATION_CENTRAL_TYPE, $storeId);

        if (!$centralType) {
            return $result;
        }

        $appId = $lsr->getConfigValueFromDb(
            $subject->getConfigPathAppId(),
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        if (!$appId) {
            $appId = Uuid::uuid4()->toString();
            $this->persistAppId($subject, $appId, $storeId);
        }

        $result[6] = $appId;
        $result[1] = 0;

        return $result;
    }

    /**
     * Persist app_id for current cron
     *
     * @param $subject
     * @param $appId
     * @param false $storeId
     */
    public function persistAppId($subject, $appId, $storeId = false)
    {
        if ($storeId) {
            $subject->resource_config->saveConfig(
                $subject->getConfigPathAppId(),
                $appId,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
        } else {
            $subject->resource_config->saveConfig(
                $subject->getConfigPathAppId(),
                $appId,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
    }
}
