<?php

namespace Ls\Customer\Block\Order\Item;

use \Ls\Omni\Helper\ItemHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Renderer
 * @package Ls\Customer\Block\Order\Item
 */
class Renderer extends Template
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/item/renderer.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * Core registry
     *
     * @var Registry
     */
    public $coreRegistry = null;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * Renderer constructor.
     * @param Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param ItemHelper $itemHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        ItemHelper $itemHelper,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->itemHelper    = $itemHelper;
        parent::__construct($context, $data);
    }

    /**
     * Return sku of order item.
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getItem()->getItemId();
    }

    /**
     * @return array|null
     */
    public function getItem()
    {
        return $this->getData('item');
    }

    /**
     * @param $qty
     * @return string
     */
    public function getFormattedQty($qty)
    {
        $qty = number_format((float)$qty, 2, '.', '');
        return $qty;
    }

    /**
     * @param $amount
     * @return float
     */
    public function getFormattedPrice($amount)
    {
        $price = $this->priceCurrency->format($amount, false, 2);
        return $price;
    }

    /**
     * @param $item
     * @return mixed
     */
    public function getItemDiscountLines()
    {
        $item      = $this->getItem();
        $orderData = $this->getData('order');
        $result    = $this->itemHelper->getOrderDiscountLinesForItem($item, $orderData, 2);
        return $result;
    }

    /**
     * @return string
     */
    public function getDiscountLabel()
    {
        return __("Save");
    }
}
