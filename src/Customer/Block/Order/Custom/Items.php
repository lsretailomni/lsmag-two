<?php

namespace Ls\Customer\Block\Order\Custom;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Items\AbstractItems;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;

/**
 * Block being used for order detail items grid
 */
class Items extends AbstractItems
{
    /** @var  LSR $lsr */
    public $lsr;

    /**
     * @var OrderHelper
     */
    public $orderHelper;
    /**
     * @var CollectionFactory|mixed|null
     */
    public $itemCollectionFactory;

    /**
     * @var Collection|null
     */
    private $itemCollection;
    /**
     * @param Context $context
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        LSR $lsr,
        OrderHelper $orderHelper,
        CollectionFactory $itemCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->lsr                   = $lsr;
        $this->orderHelper           = $orderHelper;
        $this->itemCollectionFactory = $itemCollectionFactory;
    }

    /**
     * Get items
     *
     * @return \Magento\Framework\DataObject[]
     */
    public function getItems()
    {
        if ($this->getMagOrder()) {
            return $this->itemCollection->getItems();
        }

        $orderLines = $this->getOrder()->getLines()->getSalesEntryLine();
        $this->getChildBlock("custom_order_item_renderer_custom")->setData("order", $this->getOrder());
        foreach ($orderLines as $key => $line) {
            if ($line->getItemId() == $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID)) {
                unset($orderLines[$key]);
                break;
            }
        }
        return $orderLines;
    }

    /**
     * Retrieve current order model instance
     *
     * @param $all
     * @return false|mixed|null
     */
    public function getOrder($all = false)
    {
        return $this->orderHelper->getOrder($all);
    }

    /**
     * Get magento order
     *
     * @return mixed
     */
    public function getMagOrder()
    {
        return $this->orderHelper->getGivenValueFromRegistry('current_mag_order');
    }

    /**
     * Get custom item renderer
     *
     * @param $item
     * @return string
     */
    public function getCustomItemRenderer($item)
    {
        return $this->getChildBlock("custom_order_item_renderer_custom")->setData("item", $item)->toHtml();
    }

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        if ($this->getMagOrder()) {
            $this->itemCollection = $this->itemCollectionFactory->create();
            $this->itemCollection->setOrderFilter($this->getMagOrder());
        }

        return parent::_prepareLayout();
    }
}
