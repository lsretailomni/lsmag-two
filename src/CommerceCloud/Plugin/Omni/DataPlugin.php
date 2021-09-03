<?php

namespace Ls\CommerceCloud\Plugin\Omni;

use Exception;
use \Ls\Omni\Client\Ecommerce\Entity\ReplRequest;
use \Ls\Omni\Helper\Data;
use Ramsey\Uuid\Uuid;

/**
 * Interceptor to intercept Data helper
 */
class DataPlugin
{
    /**
     * After plugin to set app_id and full_replication while fetching tender types
     *
     * @param Data $subject
     * @param $result
     * @param $baseUrl
     * @param $lsKey
     * @param $storeId
     * @return mixed
     * @throws Exception
     */
    public function afterFormulateTenderTypesRequest(Data $subject, $result, $baseUrl, $lsKey, $storeId)
    {
        $appId = Uuid::uuid4()->toString();
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
