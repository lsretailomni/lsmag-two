<?php

namespace Ls\CommerceCloud\Plugin\Cron;

use Exception;
use \Ls\CommerceCloud\Helper\Data;
use \Ls\CommerceCloud\Model\LSR;
use \Ls\Replication\Cron\AbstractReplicationTask;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Interceptor to intercept AbstractReplicationTask
 */
class AbstractReplicationTaskPlugin
{
    /**
     * @var Data
     */
    public $dataHelper;

    /**
     * @param Data $dataHelper
     */
    public function __construct(Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

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
        $centralType = $lsr->getGivenConfigInGivenScope(
            LSR::SC_REPLICATION_CENTRAL_TYPE,
            $subject->defaultScope,
            $storeId
        );

        if (!$centralType) {
            return $result;
        }
        $olderCompatibility = $lsr->getGivenConfigInGivenScope(
            LSR::SC_REPLICATION_CENTRAL_SAAS_COMPATIBILITY,
            $subject->defaultScope,
            $storeId
        );

        if ($olderCompatibility) {
            $appId = $lsr->getConfigValueFromDb(
                $subject->getConfigPathAppId(),
                ScopeInterface::SCOPE_WEBSITES,
                $storeId
            );

            if (!$appId) {
                $appId = $this->dataHelper->generateUuid();
                $this->persistAppId($subject, $appId, $storeId);
            }
            $result[1] = 0;
        } else {
            $appId = $lsr->getGivenConfigInGivenScope(
                LSR::SC_REPLICATION_CENTRAL_SAAS_APP_ID,
                $subject->defaultScope,
                $storeId
            );
        }

        $result[6] = $appId;

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
                ScopeInterface::SCOPE_WEBSITES,
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
