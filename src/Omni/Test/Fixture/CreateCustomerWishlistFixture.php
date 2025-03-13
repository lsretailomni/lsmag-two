<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Fixture;

use Laminas\EventManager\EventManagerInterface;
use Ls\Core\Model\LSR;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;
use Magento\Framework\DataObject;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;

class CreateCustomerWishlistFixture implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'product'     => '1',
        'qty'         => '1',
        'customer_id' => '1',
        'store_id'    => '1',
    ];

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var WishlistFactory
     */
    public $wishlistFactory;

    /**
     * @var WishlistProviderInterface
     */
    public $wishlistProvider;

    /**
     * @var State
     */
    public $state;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @var Wishlist
     */
    public $wishlist;

    /**
     * @var Wishlist
     */
    public $wishlistNew;

    /**
     * @var ObjectManager
     */
    public $objectManager;

    /**
     * @param CustomerSession $customerSession
     * @param State $state
     * @param WishlistFactory $wishlistFactory
     * @param WishlistProviderInterface $wishlistProvider
     * @param ProductRepositoryInterface $productRepository
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        CustomerSession $customerSession,
        State $state,
        WishlistFactory $wishlistFactory,
        WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepository,
        ManagerInterface $eventManager,
        Wishlist $wishlist
    ) {
        $this->customerSession   = $customerSession;
        $this->wishlistFactory   = $wishlistFactory;
        $this->wishlistProvider  = $wishlistProvider;
        $this->state             = $state;
        $this->productRepository = $productRepository;
        $this->eventManager      = $eventManager;
        $this->wishlist          = $wishlist;
        $this->wishlistNew       = $wishlist;
        $this->objectManager     = ObjectManager::getInstance();
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as Product::DEFAULT_DATA. Custom attributes and extension attributes
     *  can be passed directly in the outer array instead of custom_attributes or extension_attributes.
     */
    public function apply(array $data = []): ?DataObject
    {
        $this->state->setAreaCode(Area::AREA_FRONTEND);
        $data = array_merge(self::DEFAULT_DATA, $data);

        $customer = $data['customer'];
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());

        //$wishlist = $this->wishlistProvider->getWishlist();
        $wishlist = $this->wishlist->loadByCustomerId($customer->getId(), true);

        $product = $this->productRepository->getById($data['product']);

        $buyRequest = new DataObject($data);

        $result = $wishlist->addNewItem($product, $buyRequest, true);

        if ($wishlist->isObjectNew()) {
            $wishlist->save();
        }

        $this->objectManager->get(\Magento\Wishlist\Helper\Data::class)->calculate();

        $data = $this->wishlistNew->loadByCustomerId($customer->getId())->getItemCollection();

        return $wishlist;
    }
}
