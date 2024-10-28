<?php

namespace Ls\Omni\Observer\Adminhtml;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Setting grand_total & base_grand_total coming from omni
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
     * @inheritDoc
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

            if ($addressType == AbstractAddress::TYPE_SHIPPING) {
                $grandTotal = $basketData->getTotalAmount() + $total->getShippingAmount()
                    - $pointDiscount - $giftCardAmount;
                $taxAmount  = $basketData->getTotalAmount() - $basketData->getTotalNetAmount();
                $subTotal   = $basketData->getTotalAmount() + $basketData->getTotalDiscount();
                $total->setTaxAmount($taxAmount)
                    ->setBaseTaxAmount($taxAmount)
                    ->setSubtotal($subTotal - $taxAmount)
                    ->setBaseSubtotal($subTotal)
                    ->setSubtotalInclTax($subTotal)
                    ->setBaseSubtotalTotalInclTax($subTotal)
                    ->setGrandTotal($grandTotal)
                    ->setBaseGrandTotal($grandTotal)
                    ->setSubtotalWithDiscount($subTotal);
            } else {
                $total->setBaseGrandTotal(0);
                $total->setGrandTotal(0);
            }
        }
    }
}
