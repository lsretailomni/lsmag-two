<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\omni\Helper\ItemHelper;

/**
 * Class Items
 * @package Ls\Customer\Block\Order
 */
class Items extends \Magento\Sales\Block\Items\AbstractItems
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * Items constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    )
    {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        $orderLines = $this->getOrder()->getOrderLines()->getOrderLine();
        $this->getChildBlock("custom_order_item_renderer")->setData("order", $this->getOrder());
        if (!is_array($orderLines)) {
            $tmp = $orderLines;
            // @codingStandardsIgnoreStart
            $orderLines = array($tmp);
            // @codingStandardsIgnoreEnd
        }
        foreach ($orderLines as $key => $line) {
            if ($line->getItemId() == LSR::LSR_SHIPMENT_ITEM_ID) {
                unset($orderLines[$key]);
            }
        }
        return $orderLines;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @return mixed
     */
    public function getMagOrder()
    {
        return $this->coreRegistry->registry('current_mag_order');
    }

    /**
     * @return mixed
     */
    public function getShipmentOption()
    {
        return $this->coreRegistry->registry('current_shipment_option');
    }

    /**
     * @param $item
     * @return string
     */
    public function getCustomItemRenderer($item)
    {
        $html = $this->getChildBlock("custom_order_item_renderer")->setData("item", $item)->toHtml();
        return $html;
    }

}
