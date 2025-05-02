<?php

namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\Wishlist;

/**
 * This observer is responsible for wishlist sync
 */
class WishlistObserver implements ObserverInterface
{
    /** @var BasketHelper */
    private $basketHelper;

    /** @var CustomerSession $customerSession */
    private $customerSession;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var Wishlist
     */
    private $wishlist;

    /**
     * @param BasketHelper $basketHelper
     * @param CustomerSession $customerSession
     * @param LSR $LSR
     * @param Wishlist $wishlist
     */
    public function __construct(
        BasketHelper $basketHelper,
        CustomerSession $customerSession,
        LSR $LSR,
        Wishlist $wishlist
    ) {
        $this->basketHelper    = $basketHelper;
        $this->customerSession = $customerSession;
        $this->lsr             = $LSR;
        $this->wishlist        = $wishlist;
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
        /*
          * Adding condition to only process if LSR is enabled.
          */
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getOrderIntegrationOnFrontend()
        )) {
            $customerId = $this->customerSession->getCustomer()->getId();
            $wishlist   = $this->wishlist->loadByCustomerId($customerId)->getItemCollection();
            $oneList    = $this->basketHelper->fetchCurrentCustomerWishlist();
            $oneList    = $this->basketHelper->addProductToExistingWishlist($oneList, $wishlist);
            $this->basketHelper->updateWishlistAtOmni($oneList);
        }
        return $this;
    }
}
