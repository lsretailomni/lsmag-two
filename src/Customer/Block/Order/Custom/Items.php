<?php
declare(strict_types=1);

namespace Ls\Customer\Block\Order\Custom;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\DataObject;
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
     * @param Context $context
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     * @param Collection $itemCollection
     * @param CollectionFactory $itemCollectionFactory
     * @param array $data
     */
    public function __construct(
        public Context $context,
        public LSR $lsr,
        public OrderHelper $orderHelper,
        public Collection $itemCollection,
        public CollectionFactory $itemCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * This function is overriding in hospitality module
     *
     * Get items
     *
     * @return DataObject[]
     */
    public function getItems()
    {
        $order = $this->getOrder(true);
        $orderLines = [];
        $documentId = $this->_request->getParam('order_id');
        if($order) {
            $orderLines = $order->getLscMemberSalesDocLine();

            $orderLines = $orderLines && is_array($orderLines) ?
                $orderLines : (($orderLines && !is_array($orderLines)) ? [$orderLines] : []);

            $this->getChildBlock("custom_order_item_renderer_custom")->setData("order", $this->getOrder());

            foreach ($orderLines as $key => $line) {
                if ($line->getDocumentId() !== $documentId ||
                    $line->getNumber() == $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID) ||
                    $line->getEntryType() == 1
                ) {
                    unset($orderLines[$key]);
                }
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
