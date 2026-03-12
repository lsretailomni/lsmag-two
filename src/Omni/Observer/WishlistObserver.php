<?php
declare(strict_types=1);

namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\WishListHeader;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\Wishlist;
use Psr\Log\LoggerInterface;

/**
 * This observer is responsible for wishlist sync
 */
class WishlistObserver implements ObserverInterface
{
    /**
     * @param BasketHelper $basketHelper
     * @param CustomerSession $customerSession
     * @param LSR $lsr
     * @param Wishlist $wishlist
     * @param LoggerInterface $logger
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public CustomerSession $customerSession,
        public LSR $lsr,
        public Wishlist $wishlist,
        public LoggerInterface $logger
    ) {
    }

    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws NoSuchEntityException|InvalidEnumException
     */
    public function execute(Observer $observer)
    {
        try {
            if ($this->lsr->isLSR(
                $this->lsr->getCurrentStoreId(),
                false,
                $this->lsr->getBasketIntegrationOnFrontend()
            )) {
                $session = $this->customerSession;
                $data = empty($session->getBeforeWishlistUrl())
                    ? $observer->getRequest()->getParams() : null;
                $buyRequest = new DataObject($data);
                $productId = isset($data['product']) ? (int)$data['product'] : null;

                $qty = $data['qty'] ?? 1;
                $customerId = $this->customerSession->getCustomer()->getId();
                $wishlistItems = $this->wishlist->loadByCustomerId($customerId)->getItemCollection()->getItems();
                $oneList = $this->basketHelper->fetchCurrentCustomerWishlist();
                $oneListItems = $oneList && $oneList->getWishlistline()
                    ? $oneList->getWishlistline()
                    : [];

                if (!$oneList) {
                    $oneList = $this->basketHelper->createNewWishlist();
                    $oneListNo = $oneList->getWishlistno();
                } elseif ($productId) {
                    $oneList = $this->basketHelper->handleSingleProductAdd(
                        $buyRequest,
                        $productId,
                        $qty,
                        $oneList,
                        $oneListItems
                    );
                    $oneListNo = current((array)$oneList->getWishlistheader())->getData(WishListHeader::WISH_LIST_NO);
                } elseif (is_array($qty)) {
                    $oneList = $this->basketHelper->handleQtyUpdate($qty, $wishlistItems, $oneList, $oneListItems);
                    $oneListNo = current((array)$oneList->getWishlistheader())->getData(WishListHeader::WISH_LIST_NO);
                } else {
                    $oneList = $this->basketHelper->handleRemovedItems($wishlistItems, $oneList, $oneListItems);
                    $oneListNo = current((array)$oneList->getWishlistheader())->getData(WishListHeader::WISH_LIST_NO);
                }

                $oneList = $oneListNo ?
                    $this->basketHelper->getWishListFromCentralAgainstWishListNo($oneListNo) :
                    null;
                $this->basketHelper->setWishListInCustomerSession($oneList);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}
