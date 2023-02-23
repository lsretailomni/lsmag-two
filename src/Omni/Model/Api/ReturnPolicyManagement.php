<?php

namespace Ls\Omni\Model\Api;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Api\ReturnPolicyManagementInterface;
use \Ls\Omni\Client\Ecommerce\Entity\ReturnPolicy;
use \Ls\Omni\Client\Ecommerce\Entity\ReturnPolicyGet;
use \Ls\Omni\Client\Ecommerce\Entity\ReturnPolicyGetResponse;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Client\Ecommerce\Operation\ReturnPolicyGet as ReturnPolicyGetOperation;
use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Class for managing return policy related text
 */
class ReturnPolicyManagement implements ReturnPolicyManagementInterface
{

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * @var ReturnPolicyGet
     */
    private $returnPolicyGet;

    /**
     * @var ReturnPolicyGetOperation
     */
    private $returnPolicyOperation;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LSR $lsr
     * @param CacheHelper $cacheHelper
     * @param ReturnPolicyGet $returnPolicyGet
     * @param ReturnPolicyGetOperation $returnPolicyOperation
     * @param LoggerInterface $logger
     */
    public function __construct(
        LSR $lsr,
        CacheHelper $cacheHelper,
        ReturnPolicyGet $returnPolicyGet,
        ReturnPolicyGetOperation $returnPolicyOperation,
        LoggerInterface $logger
    ) {
        $this->lsr                   = $lsr;
        $this->cacheHelper           = $cacheHelper;
        $this->returnPolicyGet       = $returnPolicyGet;
        $this->returnPolicyOperation = $returnPolicyOperation;
        $this->logger                = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnPolicy($itemId, $variantId, $storeId)
    {
        if (empty($storeId)) {
            $storeId = $this->lsr->getActiveWebStore();
        }
        $cacheKey = LSR::RETURN_POLICY_CACHE . $storeId;
        if (!empty($itemId)) {
            $cacheKey = $cacheKey . '_' . $itemId;
        }
        if (!empty($variantId)) {
            $cacheKey = $cacheKey . '_' . $variantId;
        }
        $response = null;
        try {
            $response = $this->cacheHelper->getCachedContent($cacheKey);
            if ($response == false) {
                $responseArray = $this->getReturnPolicyFromService($itemId, $variantId, $storeId);
                $responseText  = reset($responseArray);
                if (!empty($responseText)) {
                    $response = $responseText->getReturnPolicyHTML();
                }
                $this->cacheHelper->persistContentInCache(
                    $cacheKey,
                    $response,
                    [Type::CACHE_TAG],
                    7200
                );
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $response;
    }

    /**
     * Return privacy policy from service
     *
     * @param $itemId
     * @param $variantId
     * @param $storeId
     * @return ReturnPolicy[]|ReturnPolicyGetResponse|ResponseInterface
     * @throws NoSuchEntityException
     */
    public function getReturnPolicyFromService($itemId, $variantId, $storeId)
    {
        if (!$this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            return null;
        }
        $entity   = $this->getPrivacyPolicyEntity($itemId, $variantId, $storeId);
        $response = $this->returnPolicyOperation->execute($entity);
        if (empty($response->getReturnPolicyGetResult()->getReturnPolicy())) {
            $entity->setVariantCode("");
            $entity   = $this->getPrivacyPolicyEntity($itemId, "", $storeId);
            $response = $this->returnPolicyOperation->execute($entity);
            if (empty($response->getReturnPolicyGetResult()->getReturnPolicy())) {
                $entity->setItemId("");
                $entity->setVariantCode("");
                $entity   = $this->getPrivacyPolicyEntity("", "", $storeId);
                $response = $this->returnPolicyOperation->execute($entity);
                $counter = 0;
                foreach ($response->getReturnPolicyGetResult()->getReturnPolicy() as $result) {
                    if ($result->getItemId() == "") {
                        $response->getReturnPolicyGetResult()->setReturnPolicy([$result]);
                    }
                    $counter ++;
                }
            }
        }

        return $response ? $response->getReturnPolicyGetResult()->getReturnPolicy() : $response;
    }

    /**
     * Get the return policy return
     *
     * @param $itemId
     * @param $variantId
     * @param $storeId
     * @return ReturnPolicyGet
     */
    public function getPrivacyPolicyEntity($itemId, $variantId, $storeId)
    {
        $entity = $this->returnPolicyGet->setItemId($itemId)
            ->setStoreId($storeId);
        if (!empty($variantId)) {
            $entity->setVariantCode($variantId);
        }
        return $entity;
    }
}
