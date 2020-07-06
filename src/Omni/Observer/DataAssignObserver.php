<?php

namespace Ls\Omni\Observer;

use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class DataAssignObserver
 * @package Ls\Omni\Observer
 */
class DataAssignObserver implements ObserverInterface
{
    /**
     * @var Proxy
     */
    private $checkoutSession;


    /**
     * DataAssignObserver constructor.
     * @param Proxy $checkoutSession
     */
    public function __construct(
        Proxy $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();

        $order->setPickupDate($quote->getPickupDate());
        if ($quote->getPickupStore()) {
            $order->setPickupStore($quote->getPickupStore());
        }
        if (!empty($this->checkoutSession->getCouponCode())) {
            $order->setCouponCode($this->checkoutSession->getCouponCode());
        }
        $order->setLsPointsSpent($quote->getLsPointsSpent());
        $order->setLsPointsEarn($quote->getLsPointsEarn());

        $order->setLsGiftCardAmountUsed($quote->getLsGiftCardAmountUsed());
        $order->setLsGiftCardNo($quote->getLsGiftCardNo());
        return $this;
    }
}
