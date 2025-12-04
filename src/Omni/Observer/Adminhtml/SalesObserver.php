<?php
declare(strict_types=1);

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
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public LoyaltyHelper $loyaltyHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $quote = $event->getQuote();
        $shippingAssignment = $event->getShippingAssignment();
        $addressType = $shippingAssignment->getShipping()->getAddress()->getAddressType();
        $total = $event->getTotal();
        $pointDiscount  = $this->loyaltyHelper->getLsPointsDiscount($quote->getLsPointsSpent());
        $giftCardAmount = $quote->getLsGiftCardAmountUsed();

        if ($pointDiscount > 0.001) {
            $quote->setLsPointsDiscount($pointDiscount);
        }

        if ($addressType == AbstractAddress::TYPE_SHIPPING) {
            $basketData = $this->basketHelper->getOneListCalculationFromCheckoutSession($quote);

            if (!empty($basketData)) {
                $pointDiscount  = $this->loyaltyHelper->getLsPointsDiscount($quote->getLsPointsSpent());
                $giftCardAmount = $quote->getLsGiftCardAmountUsed();
                $mobileTransaction = current((array)$basketData->getMobiletransaction());
                $grandTotal = $mobileTransaction->getGrossamount() + $total->getShippingInclTax()
                    - $pointDiscount - $giftCardAmount;
                $taxAmount = $mobileTransaction->getGrossamount() - $mobileTransaction->getNetamount();
                $subTotal = $mobileTransaction->getGrossamount() + $mobileTransaction->getLinediscount();

                $total->setTaxAmount($taxAmount)
                    ->setBaseTaxAmount($taxAmount)
                    ->setSubtotal($subTotal - $taxAmount)
                    ->setBaseSubtotal($subTotal)
                    ->setSubtotalInclTax($subTotal)
                    ->setBaseSubtotalTotalInclTax($subTotal)
                    ->setGrandTotal($grandTotal)
                    ->setBaseGrandTotal($grandTotal)
                    ->setSubtotalWithDiscount($subTotal);
            }

        } else {
            $total->setBaseGrandTotal(0);
            $total->setGrandTotal(0);
        }
    }
}
