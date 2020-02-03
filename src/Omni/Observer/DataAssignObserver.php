<?php

namespace Ls\Omni\Observer;

use Ls\Core\Model\LSR;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class DataAssignObserver
 * @package Ls\Omni\Observer
 */
class DataAssignObserver implements ObserverInterface
{

    /** @var LSR @var */
    private $lsr;

    /**
     * DataAssignObserver constructor.
     * @param LSR $LSR
     */

    public function __construct(
        LSR $LSR
    ) {
        $this->lsr = $LSR;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR()) {
            $quote = $observer->getQuote();
            $order = $observer->getOrder();

            $order->setPickupDate($quote->getPickupDate());
            if ($quote->getPickupStore()) {
                $order->setPickupStore($quote->getPickupStore());
            }
            $order->setLsPointsSpent($quote->getLsPointsSpent());
            $order->setLsPointsEarn($quote->getLsPointsEarn());

            $order->setLsGiftCardAmountUsed($quote->getLsGiftCardAmountUsed());
            $order->setLsGiftCardNo($quote->getLsGiftCardNo());
        }
        return $this;
    }
}
