<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Paypal\Model\CartFactory;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class AddToCartFixture implements DataFixtureInterface
{
    /**
     * @var CartFactory
     */
    public $cartFactory;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @param CartFactory $cartFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        CartFactory $cartFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->cartFactory       = $cartFactory;
        $this->productRepository = $productRepository;
    }

    public function apply(array $data = []): ?DataObject
    {
        $cart    = $this->cartFactory->create();
        $product = $this->productRepository->get('simple');
        $cart->addProduct($product->getId(), ['qty' => 1]);
    }
}
