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
     * @return string
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getItemRowTotal($item)
    {
        return $this->basketHelper->getItemRowTotal($item);
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
     * @param $price
     * @return float|int|mixed
     */
    public function getItemPriceIncludeCustomOptions($item, $price)
    {
        return $this->basketHelper->getPriceAddingCustomOptions($item, $price);
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
