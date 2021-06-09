<?php

namespace Ls\Customer\ViewModel;

use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\ItemHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
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
     *
     * @var Registry
     */
    public $coreRegistry;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @param ItemHelper $itemHelper
     * @param Registry $registry
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(ItemHelper $itemHelper, Registry $registry, PriceCurrencyInterface $priceCurrency)
    {
        $this->itemHelper = $itemHelper;
        $this->coreRegistry = $registry;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Get current order
     *
     * @return SalesEntry
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
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
            list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues($orderItem);
            $baseUnitOfMeasure = $orderItem->getProduct()->getData('uom');

            foreach ($orderLines as $index => $line) {
                if ($this->itemHelper->isValid($line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                    $discount    = $this->itemHelper->getOrderDiscountLinesForItem($line, $currentOrder, 2);
                    break;
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
