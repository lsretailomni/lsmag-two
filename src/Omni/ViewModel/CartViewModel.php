<?php
declare(strict_types=1);

namespace Ls\Omni\ViewModel;

use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

class CartViewModel implements ArgumentInterface
{
    /**
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public ItemHelper $itemHelper,
        public LoggerInterface $logger,
        public PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * Get price currency
     *
     * @return PriceCurrencyInterface
     */

    public function getPriceCurrency()
    {
        return $this->priceCurrency;
    }

    /**
     * Get Item row total
     *
     * @param $item
     * @param bool|null $inclTax defaults to the "Display Cart Subtotal" tax config when not given
     * @return string
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getItemRowTotal($item, $inclTax = null)
    {
        return $this->basketHelper->getItemRowTotal($item, $inclTax);
    }

    /**
     * Get Item row total
     *
     * @param $item
     * @return string
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getItemRowDiscount($item)
    {
        return $this->basketHelper->getItemRowDiscount($item);
    }

    /**
     * Get Item price including custom options price
     *
     * @param $item
     * @param bool|null $inclTax defaults to the "Display Cart Subtotal" tax config when not given
     * @return string
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getItemPriceIncludeCustomOptions($item, $inclTax = null)
    {
        return $this->basketHelper->getPrice($item, $inclTax);
    }

    /**
     * Whether the cart item Price/Subtotal cells should render tax-inclusive amounts.
     *
     * @return bool
     */
    public function isCartItemPriceInclTax(): bool
    {
        return $this->basketHelper->isCartItemPriceInclTax();
    }

    /**
     * @return bool
     */
    public function isCartItemPriceExclTax(): bool
    {
        return $this->basketHelper->isCartItemPriceExclTax();
    }

    /**
     * @return bool
     */
    public function isCartItemPriceBothTax(): bool
    {
        return $this->basketHelper->isCartItemPriceBothTax();
    }

    /**
     * Get One list calculation data
     *
     * @param $item
     * @return array|null
     */
    public function getOneListCalculateData($item)
    {
        $result = [];
        try {
            if ($item->getPrice() <= 0) {
                $this->basketHelper->cart->save();
            }
            $basketData = $this->basketHelper->getBasketSessionValue();
            $result     = $this->itemHelper->getOrderDiscountLinesForItem($item, $basketData);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    public function getConvertedAmount($amount)
    {
        return $this->itemHelper->convertToCurrentStoreCurrency($amount);
    }
}
