<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Items\AbstractItems;

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
     * @param Context $context
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        LSR $lsr,
        OrderHelper $orderHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->lsr                   = $lsr;
        $this->orderHelper           = $orderHelper;
    }

    /**
     * Get orderLines either using magento order or central order object
     *
     * @return mixed
     */
    public function getItems($trans)
    {
        $orderLines = $trans->getLines()->getSalesEntryLine();
        $this->getChildBlock("custom_order_item_renderer_custom")->setData("order", $trans);

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
     * Get Id value from SalesEntryGetReturnSalesResult response
     * @param $order
     * @return mixed
     */
    public function getRelevantId($order)
    {
        return $this->orderHelper->getParameterValues($order, "Id");
    }

    /**
     * Get id based on current detail
     *
     * @param $order
     * @return Phrase|string
     */
    public function getIdBasedOnDetail($order)
    {
        $detail = $this->orderHelper->getGivenValueFromRegistry('current_detail');
        $id = '';

        switch ($detail) {
            case 'order':
                $id = __('Order Transaction # %1', $this->getRelevantId($order));
                break;
            case 'shipment':
                $id = __('Shipment # %1', $this->getRelevantId($order));
                break;
            case 'invoice':
                $id = __('Invoice # %1', $this->getRelevantId($order));
                break;
            case 'creditmemo':
                $id = __('Refund # %1', $this->getRelevantId($order));
                break;
        }

        return $id;
    }
}
