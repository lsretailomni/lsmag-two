<?php

namespace Ls\OmniGraphQl\Model\Resolver\Stock;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\StockHelper;
use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;

/**
 * Resolver for finding item availability in all the stores
 */
class ItemAvailabilityOutput implements ResolverInterface
{
    /**
     * @param StockHelper $stockHelper
     * @param ProductRepositoryInterface $productRepository
     * @param DataHelper $dataHelper
     * @param LSR $lsr
     * @param LoggerInterface $logger
     */
    public function __construct(
        public StockHelper $stockHelper,
        public ProductRepositoryInterface $productRepository,
        public DataHelper $dataHelper,
        public LSR $lsr,
        public \Psr\Log\LoggerInterface $logger
    ) {
    }
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if ($this->lsr->getCurrentIndustry($this->lsr->getCurrentStoreId()) == LSR::LS_INDUSTRY_VALUE_HOSPITALITY ||
            !$this->lsr->inventoryLookupBeforeAddToCartEnabled()
        ) {
            return [
                'stores' => [],
            ];
        }

        $parentProduct = null;
        $this->logger->debug('Input parameter: ' . json_encode($args));
        if (!empty($args['parent_sku'])) {
            try {
                $parentProduct = $this->productRepository->get($args['parent_sku']);
            } catch (\Exception $exception) {
                throw new GraphQlInputException(__('Parameter "parent_sku" is incorrect!'));
            }
        }

        try {
            $product = $this->productRepository->get($args['sku']);
        } catch (\Exception $exception) {
            throw new GraphQlInputException(__('Parameter "sku" is incorrect!'));
        }
        $this->logger->debug('Product loaded with SKU: ' . $product->getSku());

        $response = $this->stockHelper->fetchAllStoresItemInStockPlusApplyJoin(
            $parentProduct ? $product->getId() : "",
            $parentProduct ? $parentProduct->getSku() : $product->getSku()
        );
        $stores   = $response->toArray()['items'];

        foreach ($stores as &$store) {
            $store = $this->dataHelper->formatStoreData($store);
        }

        return [
            'stores' => $stores,
        ];
    }
}
