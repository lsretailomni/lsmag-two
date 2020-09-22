<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Items\AbstractItems;

/**
 * Class Items
 * @package Ls\Customer\Block\Order
 */
class Items extends AbstractItems
{
    /**
     * Core registry
     *
     * @var Registry
     */
    public $coreRegistry = null;

    /** @var  LSR $lsr */
    public $lsr;

    /**
     * Items constructor.
     * @param Context $context
     * @param Registry $registry
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LSR $lsr,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->lsr          = $lsr;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        $orderLines = $this->getOrder()->getLines()->getSalesEntryLine();
        $this->getChildBlock("custom_order_item_renderer")->setData("order", $this->getOrder());
        foreach ($orderLines as $key => $line) {
            if ($line->getItemId() == $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID)) {
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
