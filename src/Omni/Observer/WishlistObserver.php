<?php

namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Wishlist\Model\Wishlist;
use Psr\Log\LoggerInterface;

/**
 * Class CartObserver
 * @package Ls\Omni\Observer
 */
class WishlistObserver implements ObserverInterface
{

    /** @var BasketHelper */
    private $basketHelper;

    /** @var Proxy $customerSession */
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
     * WishlistObserver constructor.
     * @param BasketHelper $basketHelper
     * @param LoggerInterface $logger
     * @param Proxy $customerSession
     * @param LSR $LSR
     * @param Wishlist $wishlist
     */
    public function __construct(
        BasketHelper $basketHelper,
        Proxy $customerSession,
        LSR $LSR,
        Wishlist $wishlist
    ) {
        $this->basketHelper    = $basketHelper;
        $this->customerSession = $customerSession;
        $this->lsr             = $LSR;
        $this->wishlist        = $wishlist;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    // @codingStandardsIgnoreLine
    public function execute(Observer $observer)
    {
        /*
          * Adding condition to only process if LSR is enabled.
          */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $customerId = $this->customerSession->getCustomer()->getId();
            $wishlist   = $this->wishlist->loadByCustomerId($customerId)->getItemCollection();
            $oneList    = $this->basketHelper->fetchCurrentCustomerWishlist();
            $oneList    = $this->basketHelper->addProductToExistingWishlist($oneList, $wishlist);
            $this->basketHelper->updateWishlistAtOmni($oneList);
        }

        return $this;
    }
}
