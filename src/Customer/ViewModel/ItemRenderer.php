<?php
declare(strict_types=1);

namespace Ls\Customer\ViewModel;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Item renderer being used on order detail
 */
class ItemRenderer implements ArgumentInterface
{
    /**
     * @var array
     */
    public $lines = [];

    /**
     * @param ItemHelper $itemHelper
     * @param OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param BasketHelper $basketHelper
     * @param TaxConfig $taxConfig
     */
    public function __construct(
        public ItemHelper $itemHelper,
        public OrderHelper $orderHelper,
        public PriceCurrencyInterface $priceCurrency,
        public BasketHelper $basketHelper,
        public TaxConfig $taxConfig
    ) {
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
     * This function is overriding in hospitality module
     *
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
                $this->lines = $currentOrder->getLscMemberSalesDocLine();
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
                            if ($orderLine->getParentLine() != 0 &&
                                $orderLine->getParentLine() !== $orderLine->getLineNo() &&
                                $line->getLineNo() == $orderLine->getParentLine()
                            ) {
                                $line->setPrice($line->getPrice() + $orderLine->getPrice());
                                $line->setAmount($line->getAmount() + $orderLine->getAmount());
                            }
                        }
                    }
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
     * @throws NoSuchEntityException
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

    /**
     * Get item row discount for bundle products
     *
     * @param $item
     * @return float|int
     * @throws NoSuchEntityException
     * @throws InvalidEnumException|GuzzleException
     */
    public function getItemRowDiscount($item)
    {
        $currentOrder = $this->getOrder();

        if ($currentOrder) {
            if (empty($this->lines)) {
                $this->lines = $currentOrder->getLines()->getSalesEntryLine();
            }
        }

        return !empty($this->lines) ? $this->basketHelper->getItemRowDiscount($item, $this->lines) : 0;
    }

    /**
     * Whether the Price cell should show the tax-inclusive amount
     *
     * @return bool
     */
    public function isDisplaySalesPricesInclTax()
    {
        return (bool) $this->taxConfig->displaySalesPricesInclTax();
    }

    /**
     * Whether the Price cell should show the tax-exclusive amount
     *
     * @return bool
     */
    public function isDisplaySalesPricesExclTax()
    {
        return (bool) $this->taxConfig->displaySalesPricesExclTax();
    }

    /**
     * Whether the Price cell should show both tax-inclusive and tax-exclusive amounts
     *
     * @return bool
     */
    public function isDisplaySalesPricesBoth()
    {
        return (bool) $this->taxConfig->displaySalesPricesBoth();
    }

    /**
     * Whether the Subtotal cell should show the tax-inclusive amount
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalInclTax()
    {
        return (bool) $this->taxConfig->displaySalesSubtotalInclTax();
    }

    /**
     * Whether the Subtotal cell should show the tax-exclusive amount
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalExclTax()
    {
        return (bool) $this->taxConfig->displaySalesSubtotalExclTax();
    }

    /**
     * Whether the Subtotal cell should show both tax-inclusive and tax-exclusive amounts
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalBoth()
    {
        return (bool) $this->taxConfig->displaySalesSubtotalBoth();
    }
}
