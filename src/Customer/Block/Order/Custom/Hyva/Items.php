<?php
declare(strict_types=1);

namespace Ls\Customer\Block\Order\Custom\Hyva;

use \Ls\Core\Model\LSR;
use Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
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
     * @param ItemHelper $itemHelper
     * @param array $data
     */
    public function __construct(
        public Context $context,
        public LSR $lsr,
        public OrderHelper $orderHelper,
        public Collection $itemCollection,
        public CollectionFactory $itemCollectionFactory,
        public ItemHelper $itemHelper,
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
        $detail = $this->orderHelper->getGivenValueFromRegistry('current_detail');
        if ($detail == 'creditmemo') {
            $documentId = current($this->_request->getParam('new_order_id'));
        }

        if ($order) {
            $orderLines = $order->getLscMemberSalesDocLine();

            $orderLines = $orderLines && is_array($orderLines) ?
                $orderLines : (($orderLines && !is_array($orderLines)) ? [$orderLines] : []);

            if ($this->getChildBlock("custom_order_item_renderer_custom")) {
                $this->getChildBlock("custom_order_item_renderer_custom")->setData("order", $this->getOrder());
            }

            foreach ($orderLines as $key => $line) {
                if ($line->getDocumentId() !== $documentId ||
                    $line->getNumber() == $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID) ||
                    $line->getEntryType() == 1 ||
                    $line->getEntryType() == 4
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
     * Get order header
     *
     * @param $salesEntry
     * @return \Ls\Omni\Client\CentralEcommerce\Entity\LSCMemberSalesBuffer
     */
    public function getLscMemberSalesBuffer($salesEntry)
    {
        return $this->orderHelper->getLscMemberSalesBuffer($salesEntry);
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
     * Get print all invoices url
     *
     * @param object $order
     * @return string
     */
    public function getPrintAllInvoicesUrl($order)
    {
        $reqType = $this->getRequest()->getParam('type');

        $params ['order_id'] = $order->getDocumentId();

        if ($reqType) {
            $params ['order_id'] = $this->getRequest()->getParam('order_id');
            $params ['type']     = $reqType;
        }

        return $this->getUrl('*/*/printInvoice', $params);
    }

    /**
     * Get print credit memo url
     *
     * @param object $order
     * @param object $creditmemo
     * @return string
     */
    public function getPrintCreditMemoUrl($order, $creditmemo)
    {
        $reqType = $this->getRequest()->getParam('type');

        $params['order_id']      = $order->getDocumentId();
        $params['creditmemo_id'] = $creditmemo->getId();

        if ($reqType) {
            $params['order_id'] = $this->getRequest()->getParam('order_id');
            $params['type']     = $reqType;
        }

        return $this->getUrl('*/*/PrintRefunds', $params);
    }

    /**
     * Get print all shipment url
     *
     * @param object $order
     * @return string
     */
    public function getPrintAllShipmentUrl($order)
    {
        $reqType = $this->getRequest()->getParam('type');

        $params ['order_id'] = $order->getDocumentId();

        if ($reqType) {
            $params ['order_id'] = $this->getRequest()->getParam('order_id');
            $params ['type'] = $reqType;
        }

        return $this->getUrl('*/*/printShipment', $params);
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

    /**
     * Filter Magento shipments based on order lines, as we are showing only those shipments which are related to order lines.
     * If shipment doesn't have any order line then we are not showing that shipment in shipment tab.
     *
     * @param $shipmentsCollection
     * @param array $orderLines
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function filterShipmentForOrderLines($shipmentsCollection, array $orderLines)
    {
        $requiredShipmentCollection = $shipmentsCollection;

        foreach ($shipmentsCollection as $shipment) {
            $allExists = false;
            foreach ($shipment->getAllItems() as $shipmentLine) {
                $orderItem = $shipmentLine->getOrderItem();

                if (!$orderItem->getParentItemId()) {
                    list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                        $orderItem->getSku()
                    );
                    foreach ($orderLines as $key => $orderLine) {
                        if ($itemId == $orderLine->getNumber() &&
                            $variantId == $orderLine->getVariantCode() &&
                            $uom == $orderLine->getUnitOfMeasure() &&
                            $shipmentLine->getQty() == $orderLine->getQuantity()
                        ) {
                            $allExists = true;
                        }
                    }
                }
            }

            if (!$allExists) {
                $requiredShipmentCollection->removeItemByKey($shipment->getId());
            }
        }

        return $requiredShipmentCollection;
    }
}
