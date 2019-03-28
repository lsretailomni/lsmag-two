<?php

namespace Ls\Omni\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class DataAssignObserver
 * @package Ls\Omni\Observer
 */
class DataAssignObserver implements ObserverInterface
{

    /** @var \Ls\Core\Model\LSR @var  */
    private $lsr;

    /**
     * DataAssignObserver constructor.
     * @param \Ls\Core\Model\LSR $LSR
     */

    public function __construct(
        \Ls\Core\Model\LSR $LSR
    ) {
        $this->lsr  =   $LSR;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
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
        }
        return $this;
    }
}
