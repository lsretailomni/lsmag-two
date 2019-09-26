<?php

namespace Ls\Omni\Observer;

use \Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CartObserver
 * @package Ls\Omni\Observer
 */
class WishlistObserver implements ObserverInterface
{

    /** @var BasketHelper */
    private $basketHelper;

    /** @var \Magento\Customer\Model\Session\Proxy $customerSession */
    private $customerSession;

    /**
     * @var \Ls\Core\Model\LSR
     */
    private $lsr;

    /**
     * @var \Magento\Wishlist\Model\Wishlist
     */
    private $wishlist;

    /**
     * WishlistObserver constructor.
     * @param BasketHelper $basketHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Ls\Core\Model\LSR $LSR
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     */
    public function __construct(
        BasketHelper $basketHelper,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Ls\Core\Model\LSR $LSR,
        \Magento\Wishlist\Model\Wishlist $wishlist
    ) {
        $this->basketHelper = $basketHelper;
        $this->customerSession = $customerSession;
        $this->lsr = $LSR;
        $this->wishlist = $wishlist;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    // @codingStandardsIgnoreLine
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /*
          * Adding condition to only process if LSR is enabled.
          */
        if ($this->lsr->isLSR()) {
            $customerId = $this->customerSession->getCustomer()->getId();
            $wishlist = $this->wishlist->loadByCustomerId($customerId)->getItemCollection();
            $oneList = $this->basketHelper->fetchCurrentCustomerWishlist();
            $oneList = $this->basketHelper->addProductToExistingWishlist($oneList, $wishlist);
            $this->basketHelper->updateWishlistAtOmni($oneList);
        }

        return $this;
    }
}
