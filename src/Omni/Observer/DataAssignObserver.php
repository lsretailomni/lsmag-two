<?php

namespace Ls\Omni\Observer;

use Magento\Checkout\Model\Session\Proxy;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Class for assigning and validating different extension attribute values
 */
class DataAssignObserver implements ObserverInterface
{
    /**
     * @var Proxy
     */
    private $checkoutSession;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * DataAssignObserver constructor.
     * @param Proxy $checkoutSession
     * @param Data $helper
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        Proxy $checkoutSession,
        Data $helper,
        BasketHelper $basketHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helper          = $helper;
        $this->basketHelper    = $basketHelper;
    }

    /**
     * For setting quote values
     * @param Observer $observer
     * @return $this|void
     * @throws ValidatorException
     */
    public function execute(Observer $observer)
    {
        $quote              = $observer->getQuote();
        $giftCardNo         = $quote->getLsGiftCardNo();
        $giftCardAmountUsed = $quote->getLsGiftCardAmountUsed();
        $loyaltyPointsSpent = $quote->getLsPointsSpent();
        $errorMessage       = $this->helper->orderBalanceCheck(
            $giftCardNo,
            $giftCardAmountUsed,
            $loyaltyPointsSpent,
            $this->basketHelper->getBasketSessionValue(),
            false
        );

        if ($errorMessage) {
            throw new ValidatorException($errorMessage);
        }
        $order = $observer->getOrder();

        if ($quote->getPickupDateTimeslot()) {
            $order->setPickupDateTimeslot($quote->getPickupDateTimeslot());
        }

        if ($quote->getPickupStore()) {
            $order->setPickupStore($quote->getPickupStore());
        }

        if (!empty($quote->getCouponCode())) {
            $order->setCouponCode($quote->getCouponCode());
        }

        $order->setLsPointsSpent($loyaltyPointsSpent);
        $order->setLsPointsEarn($quote->getLsPointsEarn());

        $order->setLsGiftCardAmountUsed($giftCardAmountUsed);
        $order->setLsGiftCardNo($giftCardNo);

        return $this;
    }
}
