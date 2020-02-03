<?php

namespace Ls\Omni\Observer;

use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesObserver
 * @package Ls\Omni\Observer
 */
class SalesObserver implements ObserverInterface
{
    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var LoyaltyHelper
     */
    private $loyaltyHelper;

    /**
     * SalesObserver constructor.
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     */
    public function __construct(
        BasketHelper $basketHelper,
        LoyaltyHelper $loyaltyHelper
    ) {
        $this->basketHelper  = $basketHelper;
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $event              = $observer->getEvent();
        $quote              = $event->getQuote();
        $shippingAssignment = $event->getShippingAssignment();
        $addressType        = $shippingAssignment->getShipping()->getAddress()->getAddressType();
        $total              = $event->getTotal();

        $basketData = $this->basketHelper->getBasketSessionValue();
        if (!empty($basketData)) {
            $pointDiscount  = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            $giftCardAmount = $quote->getLsGiftCardAmountUsed();
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }
            if ($addressType == "shipping") {
                $total->setBaseGrandTotal(
                    $basketData->getTotalAmount() + $total->getShippingAmount() - $pointDiscount - $giftCardAmount
                );
                $total->setGrandTotal(
                    $basketData->getTotalAmount() + $total->getShippingAmount() - $pointDiscount - $giftCardAmount
                );
            } else {
                $total->setBaseGrandTotal(0);
                $total->setGrandTotal(0);
            }
        }
    }
}
