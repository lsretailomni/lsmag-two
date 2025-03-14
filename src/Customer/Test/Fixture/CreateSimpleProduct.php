<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Fixture;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class CreateSimpleProduct implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'id'               => 1,
        'attribute_set_id' => 4,
        'name'             => 'Simple Product',
        'sku'              => 'simple',
        'price'            => 10,
        'tax_class_id'     => 0,
        'meta_title'       => 'meta title',
        'meta_keyword'     => 'meta keyword',
        'visibility'       => Visibility::VISIBILITY_BOTH,
        'status'           => Status::STATUS_ENABLED,
        'stock_data'       => [
            'qty'          => 100,
            'is_in_stock'  => 1,
            'manage_stock' => 1,
        ],
        'website_ids'      => [1]
    ];

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var ProductInterfaceFactory */
    public $productFactory;

    /** @var ProductRepositoryInterface */
    public $productRepository;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ProductInterfaceFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->storeManager          = $storeManager;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * Apply fixture data
     *
     * @param array $data
     * @return DataObject|null
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function apply(array $data = []): ?DataObject
    {
        $data = array_merge(self::DEFAULT_DATA, $data);
        $product = $this->productFactory->create();
        foreach ($data as $index => $value) {
            $product->setData($index, $value);
        }

        return $this->productRepository->save($product);
    }
}
