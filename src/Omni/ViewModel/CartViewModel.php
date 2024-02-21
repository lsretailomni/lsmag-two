<?php

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
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        LoggerInterface $logger,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->itemHelper = $itemHelper;
        $this->basketHelper = $basketHelper;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;
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
}
