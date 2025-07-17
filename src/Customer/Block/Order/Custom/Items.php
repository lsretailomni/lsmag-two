<?php

namespace Ls\Customer\Block\Order\Custom;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
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
    /**
     * @param Context $context
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
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
     * Get items
     *
     * @return \Magento\Framework\DataObject[]
     */
    public function getItems()
    {
        $type = $this->_request->getParam('type');
        $order = $this->getOrder(true);
        if ($this->getMagOrder()) {
            $magentoOrder = $this->getMagOrder();

            if (!empty($magentoOrder) && !empty($order->getStoreCurrency())) {
                if ($order->getStoreCurrency() != $magentoOrder->getOrderCurrencyCode()) {
                    $magentoOrder = null;
                }
            }
            return $this->itemCollection->getItems();
        }

        $orderLines = $order->getLscMemberSalesDocLine();
        if (!$orderLines) {
            return [];
        }
        if (!is_array($orderLines)) {
            $orderLines = [$orderLines];
        }
        $options = [];
        $this->getChildBlock("custom_order_item_renderer_custom")->setData("order", $this->getOrder());
        foreach ($orderLines as $key => $line) {
            foreach ($orderLines as $orderLine) {
                if ($line->getLineNo() == $orderLine->getParentLine()) {
                    $line->setPrice($line->getPrice() + $orderLine->getAmount() / $orderLine->getQuantity());
                    $line->setAmount($line->getAmount() + $orderLine->getAmount());
                }
            }
//            if ($line->getLineNo() != $line->getParentLine()) {
//                unset($orderLines[$key]);
//            }
            if ($line->getNumber() == $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID)) {
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
