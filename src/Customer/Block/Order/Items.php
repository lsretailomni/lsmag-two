<?php
declare(strict_types=1);

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
    /**
     * @param Context $context
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        public LSR $lsr,
        public OrderHelper $orderHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get orderLines either using magento order or central order object
     *
     * @return array
     */
    public function getItems($trans)
    {
        $order = $this->getOrder(true);
        $orderLines = $order->getLscMemberSalesDocLine();
        $this->getChildBlock("custom_order_item_renderer_custom")->setData("order", $trans);
        $documentId = $trans->getDocumentId();

        foreach ($orderLines as $key => $line) {
            if (($line->getDocumentId() !== $documentId) ||
                $line->getNumber() == $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID) ||
                $line->getEntryType() == 1
            ) {
                unset($orderLines[$key]);
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
     *
     * @param $order
     * @return mixed
     */
    public function getRelevantId($order)
    {
        return $this->orderHelper->getParameterValues($order, "Document ID");
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

    /**
     * For checking is shipment tab
     *
     * @return bool
     */
    public function checkIsShipment()
    {
        return ($this->orderHelper->getGivenValueFromRegistry('current_detail') == 'shipment');
    }

    /**
     * Get current transaction
     *
     * @return array
     */

    public function getCurrentTransaction()
    {
        $order = $this->getOrder(true);
        $documentId = $this->_request->getParam('order_id');
        $isCreditMemo = $this->_request->getActionName() == 'creditmemo' ||
            $this->_request->getActionName() == 'printRefunds';
        $requiredTransaction = [];
        $transactions = $order->getLscMemberSalesBuffer() && is_array($order->getLscMemberSalesBuffer()) ?
            $order->getLscMemberSalesBuffer() :
            ($order->getLscMemberSalesBuffer() ? [$order->getLscMemberSalesBuffer()] : []);

        foreach ($transactions as $transaction) {
            if ($transaction->getDocumentId() == $documentId ||
                ($isCreditMemo && $transaction->getSaleIsReturnSale())
            ) {
                $requiredTransaction[] = $transaction;
                if (!$isCreditMemo) {
                    break;
                }
            }
        }

        return $requiredTransaction;
    }
}
