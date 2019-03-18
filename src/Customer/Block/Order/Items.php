<?php
namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;

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
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        $orderLines = $this->getOrder()->getOrderLines()->getOrderLine();
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
     * @param $item
     * @return string
     */
    public function getCustomItemRenderer($item)
    {
        $html = $this->getChildBlock("custom_order_item_renderer")->setData("item", $item)->toHtml();
        return $html;
    }
}
