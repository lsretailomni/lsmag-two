<?php

namespace Ls\Omni\Block\Cart\Item;

use Exception;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Overriding the default item renderer
 *
 */
class Renderer extends \Magento\Checkout\Block\Cart\Item\Renderer
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
     * @param $item
     * @return array|null
     */
    public function getOneListCalculateData($item)
    {
        try {
            $this->basketHelper = $this->getBasketHelper();
            $this->itemHelper   = $this->getBasketHelper()->getItemHelper();
            if ($item->getPrice() <= 0) {
                $this->basketHelper->cart->save();
            }
            $basketData = $this->basketHelper->getBasketSessionValue();
            $result     = $this->itemHelper->getOrderDiscountLinesForItem($item, $basketData);
            return $result;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @return BasketHelper
     */
    private function getBasketHelper()
    {
        return ObjectManager::getInstance()
            ->get('\Ls\Omni\Helper\BasketHelper');
    }

    /**
     * @return PriceCurrencyInterface
     */

    public function getPriceCurrency()
    {
        return $this->priceCurrency;
    }

    /**
     * @return array
     */
    public function getOptionList()
    {
        return $this->_productConfig->getOptions(parent::getItem());
    }

    /**
     * @param $item
     * @return string
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getItemRowTotal($item)
    {
        $basketHelper = $this->getBasketHelper();
        return $basketHelper->getItemRowTotal($item);
    }
}
