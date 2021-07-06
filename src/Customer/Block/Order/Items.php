<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Items\AbstractItems;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;

/**
 * Block being used for order detail items grid
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
     * @var CollectionFactory|mixed|null
     */
    public $itemCollectionFactory;

    /**
     * @var Collection|null
     */
    private $itemCollection;

    /**
     * Items constructor.
     * @param Context $context
     * @param Registry $registry
     * @param LSR $lsr
     * @param array $data
     * @param CollectionFactory|null $itemCollectionFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LSR $lsr,
        CollectionFactory $itemCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry          = $registry;
        $this->lsr                   = $lsr;
        $this->itemCollectionFactory = $itemCollectionFactory;
    }

    /**
     * Get orderLines either using magento order or central order object
     *
     * @return mixed
     */
    public function getItems()
    {
        if ($this->getMagOrder()) {
            return $this->itemCollection->getItems();
        }

        $orderLines = $this->getOrder()->getLines()->getSalesEntryLine();
        $this->getChildBlock("custom_order_item_renderer")->setData("order", $this->getOrder());
        foreach ($orderLines as $key => $line) {
            if ($line->getItemId() == $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID)) {
                unset($orderLines[$key]);
                break;
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
     * @param $item
     * @return string
     */
    public function getCustomItemRenderer($item)
    {
        return $this->getChildBlock("custom_order_item_renderer")->setData("item", $item)->toHtml();
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
