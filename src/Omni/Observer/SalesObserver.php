<?php
declare(strict_types=1);

namespace Ls\Omni\Observer;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * This observer is responsible for setting grand_total & base_grand_total coming from omni
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
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws NoSuchEntityException
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $quote = $event->getQuote();
        $shippingAssignment = $event->getShippingAssignment();
        $addressType        = $shippingAssignment->getShipping()->getAddress()->getAddressType();
        $total              = $event->getTotal();
        $loyaltyPointsSpent = $quote->getLsPointsSpent();
        $pointDiscount = 0;
        if (!empty($loyaltyPointsSpent)) {
            $pointDiscount = $this->loyaltyHelper->getLsPointsDiscount($quote->getLsPointsSpent());
        }

        $giftCardAmount     = $quote->getLsGiftCardAmountUsed();

        if ($pointDiscount > 0.001) {
            $quote->setLsPointsDiscount($pointDiscount);
        }

        if (($quote->isVirtual() && $addressType == AbstractAddress::TYPE_BILLING) ||
            (!$quote->isVirtual() && $addressType == AbstractAddress::TYPE_SHIPPING)) {
            $basketData = $this->basketHelper->getBasketSessionValue();

            if (!empty($basketData)) {
                $mobileTransaction = current((array) $basketData->getMobiletransaction());
                $grandTotal = $mobileTransaction->getGrossamount() + $total->getShippingInclTax()
                    - $pointDiscount - $giftCardAmount;
                $taxAmount = $mobileTransaction->getGrossamount() - $mobileTransaction->getNetamount();
                $subTotal = $mobileTransaction->getGrossamount() + $mobileTransaction->getLinediscount();
                $total->setTaxAmount($taxAmount)
                    ->setBaseTaxAmount($this->basketHelper->itemHelper->convertToBaseCurrency($taxAmount))
                    ->setSubtotal($mobileTransaction->getNetamount())
                    ->setBaseSubtotal(
                        $this->basketHelper->itemHelper->convertToBaseCurrency($mobileTransaction->getNetamount())
                    )
                    ->setSubtotalInclTax($subTotal)
                    ->setBaseSubtotalInclTax($this->basketHelper->itemHelper->convertToBaseCurrency($subTotal))
                    ->setBaseSubtotalTotalInclTax($this->basketHelper->itemHelper->convertToBaseCurrency($subTotal))
                    ->setGrandTotal($grandTotal)
                    ->setBaseGrandTotal($this->basketHelper->itemHelper->convertToBaseCurrency($grandTotal));
            } else {
                if (($addressType == AbstractAddress::TYPE_SHIPPING &&
                    $this->basketHelper->getLsrModel()->isEnabled())
                ) {
                    $address = $shippingAssignment->getShipping()->getAddress();
                    $address->setSubtotal($total->getSubtotal());
                    if (!$quote->isVirtual()) {
                        $address->setSubtotalInclTax($total->getSubtotal());
                    }

                }
            }
        }

        return $this;
    }
}
