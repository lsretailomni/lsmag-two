<?php

namespace Ls\CommerceCloud\Plugin\Omni;

use Exception;
use \Ls\CommerceCloud\Helper\Data;
use \Ls\CommerceCloud\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplRequest;

/**
 * Interceptor to intercept Data helper
 */
class DataPlugin
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
     * After plugin to set app_id and full_replication while fetching tender types
     *
     * @param \Ls\Omni\Helper\Data $subject
     * @param $result
     * @param $baseUrl
     * @param $lsKey
     * @param $storeId
     * @param $scopeId
     * @return mixed
     * @throws Exception
     */
    public function afterFormulateTenderTypesRequest(
        \Ls\Omni\Helper\Data $subject,
        $result,
        $baseUrl,
        $lsKey,
        $storeId,
        $scopeId
    ) {
        $centralType = $subject->lsr->getWebsiteConfig(LSR::SC_REPLICATION_CENTRAL_TYPE, $scopeId);

        if (!$centralType) {
            return $result;
        }
        $olderCompatibility = $subject->lsr->getWebsiteConfig(LSR::SC_REPLICATION_CENTRAL_SAAS_COMPATIBILITY, $scopeId);
        if ($olderCompatibility) {
            $appId = $this->dataHelper->generateUuid();
        } else {
            $appId = $subject->lsr->getWebsiteConfig(LSR::SC_REPLICATION_CENTRAL_SAAS_APP_ID, $scopeId);
        }
        $result->getOperationInput()->setReplRequest(
            (new ReplRequest())
                ->setBatchSize(1000)
                ->setFullReplication(0)
                ->setLastKey('')
                ->setStoreId($storeId)
                ->setAppId($appId)
        );

        return $result;
    }
}
