<?php

namespace Ls\Customer\ViewModel;

use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Item renderer being used on order detail
 */
class ItemRenderer implements ArgumentInterface
{
    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var array
     */
    public $lines = [];

    /**
     * @param ItemHelper $itemHelper
     * @param OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(ItemHelper $itemHelper, OrderHelper $orderHelper, PriceCurrencyInterface $priceCurrency)
    {
        $this->itemHelper    = $itemHelper;
        $this->orderHelper   = $orderHelper;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Get current order
     *
     * @return SalesEntry
     */
    public function getOrder()
    {
        return $this->orderHelper->getGivenValueFromRegistry('current_order');
    }

    /**
     * Get matched line and discount info
     *
     * @param $orderItem
     * @return array
     * @throws NoSuchEntityException
     */
    public function getDiscountInfoGivenOrderItem($orderItem)
    {
        $discount     = [];
        $line         = null;
        $currentOrder = $this->getOrder();

        if ($currentOrder) {
            if (empty($this->lines)) {
                $this->lines = $currentOrder->getLines()->getSalesEntryLine();
            }
            list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                $orderItem->getSku()
            );
            $baseUnitOfMeasure = $orderItem->getProduct()->getData('uom');

            foreach ($this->lines as $index => $line) {
                if ($this->itemHelper->isValid($orderItem, $line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                    $discount     = $this->itemHelper->getOrderDiscountLinesForItem($line, $currentOrder, 2);
                    $options      = $orderItem->getProductOptions();
                    $optionsCheck = ($options) ? isset($options['options']) : null;
                    if ($optionsCheck) {
                        foreach ($this->lines as $orderLine) {
                            if ($line->getLineNumber() == $orderLine->getParentLine() &&
                                $orderLine->getParentLine() != 0) {
                                $line->setPrice($line->getPrice() + $orderLine->getPrice());
                                $line->setAmount($line->getAmount() + $orderLine->getAmount());
                            }
                        }
                    }

                    unset($this->lines[$index]);
                    break;
                } else {
                    $line = null;
                }
            }
        }

        return [$discount, $line];
    }

    /**
     * Get formatted price
     *
     * @param $amount
     * @return float
     */
    public function getFormattedPrice($amount)
    {
        $order = $this->getOrder();
        return $this->orderHelper->getPriceWithCurrency(
            $this->priceCurrency,
            $amount,
            $order->getStoreCurrency(),
            $order->getStoreId()
        );
    }
}
