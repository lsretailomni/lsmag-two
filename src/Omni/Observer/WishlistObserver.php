<?php
declare(strict_types=1);

namespace Ls\Omni\Observer;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\Wishlist;

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
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public CustomerSession $customerSession,
        public LSR $lsr,
        public Wishlist $wishlist
    ) {
    }

    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws NoSuchEntityException|GuzzleException|InvalidEnumException
     * @throws AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        /*
          * Adding condition to only process if LSR is enabled.
          */
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getOrderIntegrationOnFrontend()
        )) {
            $customerId = $this->customerSession->getCustomer()->getId();
            $wishlistItems = $this->wishlist->loadByCustomerId($customerId)->getItemCollection()->getItems();

            if (!empty($wishlistItems)) {
                $oneList = $this->basketHelper->fetchCurrentCustomerWishlist();
                $oneList = $this->basketHelper->addProductToExistingWishlist($oneList, $wishlistItems);
                $this->basketHelper->update($oneList, 2);
            }
        }

        return $this;
    }
}
