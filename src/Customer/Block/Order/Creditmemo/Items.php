<?php

namespace Ls\Customer\Block\Order\Creditmemo;

use \Ls\Core\Model\LSR;
use Ls\Omni\Helper\OrderHelper;
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
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * Items constructor.
     * @param Context $context
     * @param Registry $registry
     * @param LSR $lsr
     * @param CollectionFactory $itemCollectionFactory
     * @param OrderHelper $orderHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LSR $lsr,
        CollectionFactory $itemCollectionFactory,
        OrderHelper $orderHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry          = $registry;
        $this->lsr                   = $lsr;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Get orderLines either using magento order or central order object
     * @param $salesEntryItem
     * @return array
     */
    public function getItems($salesEntryItem)
    {
        $orderLines = $this->getLines($salesEntryItem);
        $orderLinesArr = [];
        $this->getChildBlock("custom_order_item_renderer")->setData("order", $this->getOrder());
        foreach ($orderLines as $key => $line) {
            if ($line->getItemId() != $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID)) {
                $orderLinesArr[$key] = $line;
            }
        }

        return $orderLinesArr;
    }

    /**
     * Fetch Lines node from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     * depending on the structure of SalesEntry node.
     * @param $salesEntryItem
     * @return mixed
     */
    public function getLines($salesEntryItem)
    {
        return $this->orderHelper->getParameterValues($salesEntryItem, "Lines");
    }

    /**
     * Get current central order from registry.
     * @return mixed
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * Get current magento order from registry
     * @return mixed
     */
    public function getMagOrder()
    {
        return $this->coreRegistry->registry('current_mag_order');
    }

    /**
     * Get custom order item renderer
     * @param $item
     * @return string
     */
    public function getCustomItemRenderer($item): string
    {
        return $this->getChildBlock("custom_order_item_renderer")->setData("item", $item)->toHtml();
    }

    /**
     * Get Id value from SalesEntryGetReturnSalesResult response
     * @param $order
     * @return mixed
     */
    public function getRefundId($order)
    {
        return $this->orderHelper->getParameterValues($order, "Id");
    }

    /**
     * Generate Print Refund Url
     * @param $order
     * @return string
     */
    public function getPrintAllRefundsUrl($order): string
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $idType  = $this->orderHelper->getParameterValues($order, "IdType");
        return $this->getUrl(
            '*/*/printRefunds',
            [
                'order_id' => $orderId,
                'type'     => $idType
            ]
        );
    }

    /**
     * Get Sales Return Entry details
     * @param $centralOrder
     * @return mixed
     */
    public function getSalesReturnEntires($centralOrder)
    {
        return $centralOrder->getSalesEntry();
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
