<?php

namespace Ls\OmniGraphQl\Model\Resolver\Stock;

use \Ls\Omni\Helper\StockHelper;
use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for finding item availability in all the stores
 */
class ItemAvailabilityOutput implements ResolverInterface
{
    /**
     * @var StockHelper
     */
    public $stockHelper;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @var DataHelper
     */
    public $dataHelper;

    /**
     * @param StockHelper $stockHelper
     * @param ProductRepositoryInterface $productRepository
     * @param DataHelper $dataHelper
     */
    public function __construct(
        StockHelper $stockHelper,
        ProductRepositoryInterface $productRepository,
        DataHelper $dataHelper
    ) {
        $this->stockHelper       = $stockHelper;
        $this->productRepository = $productRepository;
        $this->dataHelper        = $dataHelper;
    }
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $parentProduct = null;

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
