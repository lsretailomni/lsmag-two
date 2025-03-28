<?php

namespace Ls\Omni\Observer;

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
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $event              = $observer->getEvent();
        $quote              = $event->getQuote();
        $shippingAssignment = $event->getShippingAssignment();
        $addressType        = $shippingAssignment->getShipping()->getAddress()->getAddressType();
        $total              = $event->getTotal();
        $basketData         = $this->basketHelper->getBasketSessionValue();
        if (!empty($basketData)) {
            $pointDiscount  = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            $giftCardAmount = $quote->getLsGiftCardAmountUsed();

            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }

            if (($quote->isVirtual() && $addressType == AbstractAddress::TYPE_BILLING) ||
                (!$quote->isVirtual() && $addressType == AbstractAddress::TYPE_SHIPPING)) {
                $grandTotal = $basketData->getTotalAmount() + $total->getShippingInclTax()
                    - $pointDiscount - $giftCardAmount;
                $taxAmount  = $basketData->getTotalAmount() - $basketData->getTotalNetAmount();
                $subTotal   = $basketData->getTotalAmount() + $basketData->getTotalDiscount();
                $total->setTaxAmount($taxAmount)
                    ->setBaseTaxAmount($this->basketHelper->itemHelper->convertToBaseCurrency($taxAmount))
                    ->setSubtotal($basketData->getTotalNetAmount())
                    ->setBaseSubtotal($this->basketHelper->itemHelper->convertToBaseCurrency($basketData->getTotalNetAmount()))
                    ->setSubtotalInclTax($subTotal)
                    ->setBaseSubtotalInclTax($this->basketHelper->itemHelper->convertToBaseCurrency($subTotal))
                    ->setBaseSubtotalTotalInclTax($this->basketHelper->itemHelper->convertToBaseCurrency($subTotal))
                    ->setGrandTotal($grandTotal)
                    ->setBaseGrandTotal($this->basketHelper->itemHelper->convertToBaseCurrency($grandTotal));
            }
        } else {
            if (($addressType == AbstractAddress::TYPE_SHIPPING && $this->basketHelper->getLsrModel()->isEnabled())) {
                $address = $shippingAssignment->getShipping()->getAddress();
                $address->setSubtotal($total->getSubtotal());
                $address->setSubtotalInclTax($total->getSubtotal());
            }
        }
    }
}
