<?php

namespace Ls\Omni\Block\Cart\Item;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\omni\Helper\ItemHelper;

/**
 * Class Renderer
 * @package Ls\Omni\Block\Cart\Item
 */
class Renderer extends \Magento\Checkout\Block\Cart\Item\Renderer
{
    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * ItemHelper
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
            $this->itemHelper = $this->getBasketHelper()->getItemHelper();
            if ($item->getPrice() <= 0) {
                $this->basketHelper->cart->save();
            }
            $basketData = $this->basketHelper->getBasketSessionValue();
            $result = $this->itemHelper->getOrderDiscountLinesForItem($item, $basketData);
            return $result;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @return \Ls\Omni\Helper\BasketHelper
     */
    private function getBasketHelper()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Ls\Omni\Helper\BasketHelper');
    }

    /**
     * @return \Magento\Framework\Pricing\PriceCurrencyInterface
     */

    public function getPriceCurrency(){
        return $this->priceCurrency;
    }

    /**
     * @return array
     */
    public function getOptionList()
    {
        return $this->_productConfig->getOptions(parent::getItem());
    }
    
}
