<?php
declare(strict_types=1);

namespace Ls\Omni\Test\Fixture;

use Ls\Core\Model\LSR;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResource;
use Magento\Catalog\Api\ProductRepositoryInterface;

class WishlistWithItemFixture implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'customer_id' => null,
        'product_id'  => null,
        'qty'         => 1,
    ];

    /**
     * @param WishlistFactory $wishlistFactory
     * @param WishlistResource $wishlistResource
     * @param ProductRepositoryInterface $productRepository
     * @param DataMerger $dataMerger
     * @param ManagerInterface $eventManager
     * @param HttpRequest $request
     * @param State $state
     * @param CustomerSession $customerSession
     */
    public function __construct(
        private readonly WishlistFactory $wishlistFactory,
        private readonly WishlistResource $wishlistResource,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly DataMerger $dataMerger,
        private readonly ManagerInterface $eventManager,
        private readonly HttpRequest $request,
        private readonly State $state,
        private readonly CustomerSession $customerSession
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $areaCode = $data['area_code'] ?? Area::AREA_FRONTEND;
        $this->state->setAreaCode($areaCode);
//        $data = $this->dataMerger->merge(self::DEFAULT_DATA, $data);
        $customer = $data['customer'] ?? null;
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId($customer->getId(), true);

        $product = $this->productRepository->getById($data['product_id']);
        $buyRequest = new DataObject(['qty' => $data['qty'] ?? 1]);
        $wishlist->addNewItem($product, $buyRequest);
        $this->wishlistResource->save($wishlist);

        $this->request->setParams([
            'product' => $data['product_id'],
            'qty'     => $data['qty'] ?? 1,
        ]);

        $this->eventManager->dispatch(
            'controller_action_postdispatch_wishlist_index_add',
            ['request' => $this->request]
        );

        return $wishlist;
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId($data->getCustomerId());
        if ($wishlist->getId()) {
            $this->wishlistResource->delete($wishlist);
        }
    }
}
