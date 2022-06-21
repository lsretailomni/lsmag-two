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
        $discount = [];
        $line = null;
        $currentOrder = $this->getOrder();

        if ($currentOrder) {
            $orderLines = $currentOrder->getLines();
            list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                $orderItem->getProductId(),
                $orderItem->getSku()
            );
            $baseUnitOfMeasure = $orderItem->getProduct()->getData('uom');

            foreach ($orderLines as $index => $line) {
                if ($this->itemHelper->isValid($line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                    $discount    = $this->itemHelper->getOrderDiscountLinesForItem($line, $currentOrder, 2);
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
        return $this->priceCurrency->format($amount, false, 2);
    }
}
