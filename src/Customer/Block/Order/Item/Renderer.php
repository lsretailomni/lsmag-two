<?php

namespace Ls\Customer\Block\Order\Item;

use Magento\Framework\Pricing\PriceCurrencyInterface;

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
     * Renderer constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
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
}