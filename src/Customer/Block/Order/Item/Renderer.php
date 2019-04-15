<?php

namespace Ls\Customer\Block\Order\Item;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use \Ls\Core\Model\LSR;
use \Ls\omni\Helper\ItemHelper;

/**
 * Class Renderer
 * @package Ls\Customer\Block\Order\Item
 */
class Renderer extends \Magento\Framework\View\Element\Template
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
     * @var \Magento\Framework\Registry
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
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        PriceCurrencyInterface $priceCurrency,
        ItemHelper $itemHelper,
        array $data = []
    )
    {
        $this->priceCurrency = $priceCurrency;
        $this->itemHelper = $itemHelper;
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
        $item = $this->getItem();
        $orderData = $this->getData('order');
        $result = $this->itemHelper->getOrderDiscountLinesForItem($item, $orderData, 2);
        return $result;
    }

    /**
     * @return string
     */
    public function getDiscountLabel()
    {
        return LSR::LS_DISCOUNT_PRICE_PERCENTAGE_TEXT;
    }
}