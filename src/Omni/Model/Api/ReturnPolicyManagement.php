<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Api;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Api\ReturnPolicyManagementInterface;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetReturnPolicy;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetReturnPolicy as ReturnPolicyGetOperation;
use \Ls\Omni\Model\Cache\Type;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Class for managing return policy related text
 */
class ReturnPolicyManagement implements ReturnPolicyManagementInterface
{
    /**
     * @param LSR $lsr
     * @param CacheHelper $cacheHelper
     * @param GetReturnPolicy $returnPolicyGet
     * @param ReturnPolicyGetOperation $returnPolicyOperation
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     */
    public function __construct(
        public LSR $lsr,
        public CacheHelper $cacheHelper,
        public GetReturnPolicy $returnPolicyGet,
        public ReturnPolicyGetOperation $returnPolicyOperation,
        public LoggerInterface $logger,
        public ProductRepository $productRepository
    ) {
    }

    /**
     * Get return policy data
     *
     * @param string $itemId
     * @param string|null $variantId
     * @param string $storeId
     * @param boolean $variantIdIsSku
     * @return mixed
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function getReturnPolicy(string $itemId, ?string $variantId, string $storeId, bool $variantIdIsSku = false)
    {
        if (empty($storeId)) {
            $storeId = $this->lsr->getActiveWebStore();
        }

        if (!empty($variantId)) {
            if ($variantIdIsSku) {
                $product = $this->productRepository->get($variantId);
            } else {
                $product = $this->productRepository->getById($variantId);
            }
        } else {
            $product = $this->productRepository->get($itemId);
        }

        $itemId = $product->getLsrItemId();
        $variantId = $product->getLsrVariantId();
        $cacheKey = LSR::RETURN_POLICY_CACHE . $storeId;
        if (!empty($itemId)) {
            $cacheKey = $cacheKey . '_' . $itemId;
        }
        if (!empty($variantId)) {
            $cacheKey = $cacheKey . '_' . $itemId . '_' . $variantId;
        }
        $response = null;
        try {
            $response = $this->cacheHelper->getCachedContent($cacheKey);
            if ($response == false) {
                $responseArray = $this->getReturnPolicyFromService($itemId, $variantId, $storeId);

                if (is_array($responseArray)) {
                    $responseText = reset($responseArray);
                    if (!empty($responseText)) {
                        $response = $responseText->getReturnPolicyHtml();
                    }
                    $this->cacheHelper->persistContentInCache(
                        $cacheKey,
                        $response,
                        [Type::CACHE_TAG],
                        7200
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $response;
    }

    /**
     * Return privacy policy from service
     *
     * @param string $itemId
     * @param string|null $variantId
     * @param string $storeId
     * @return array|null
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function getReturnPolicyFromService(string $itemId, ?string $variantId, string $storeId)
    {
        if (!$this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            return null;
        }
        $input = [
            GetReturnPolicy::STORE_NO => $storeId,
            GetReturnPolicy::ITEM_NO => $itemId,
        ];

        if (!empty($variantId)) {
            $input[GetReturnPolicy::VARIANT_CODE] = $variantId;
        }
        $this->returnPolicyOperation->setOperationInput($input);
        $response = $this->returnPolicyOperation->execute();

        return $response && $response->getResponsecode() == '0000' ?
            $response->getGetreturnpolicyxml()->getReturnpolicy() : null;
    }
}
