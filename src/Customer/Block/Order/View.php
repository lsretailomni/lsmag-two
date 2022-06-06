<?php

namespace Ls\Customer\Block\Order;

class View extends AbstractOrderBlock
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/view.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * Get Payment info html
     *
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

    /**
     * Get Invoice Id
     *
     * @return mixed
     */
    public function getInvoiceId()
    {
        return $this->orderHelper->getGivenValueFromRegistry('current_invoice_id');
    }

    /**
     * Get Invoice option
     *
     * @return mixed
     */
    public function getInvoiceOption()
    {
        return $this->orderHelper->getGivenValueFromRegistry('current_invoice_option');
    }

    /**
     * Get shipment option
     *
     * @return mixed
     */
    public function getShipmentOption()
    {
        return $this->orderHelper->getGivenValueFromRegistry('current_shipment_option');
    }

    /**
     * Get hide Shipping links
     *
     * @return mixed
     */
    public function hideShippingLinks()
    {
        return $this->orderHelper->getGivenValueFromRegistry('hide_shipping_links');
    }

    /**
     * Get print all invoices url
     *
     * @param object $order
     * @return string
     */
    public function getPrintAllInvoicesUrl($order)
    {
        return $this->getUrl('*/*/printInvoice', ['order_id' => $order->getDocumentId()]);
    }

    /**
     * Get print all shipment url
     *
     * @param object $order
     * @return string
     */
    public function getPrintAllShipmentUrl($order)
    {
        return $this->getUrl('*/*/printShipment', ['order_id' => $order->getDocumentId()]);
    }

    /**
     * Generate Print Refund Url
     * @return string
     */
    public function getPrintAllRefundsUrl(): string
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $idType  = $this->getRequest()->getParam('type');
        return $this->getUrl(
            '*/*/printRefunds',
            [
                'order_id' => $orderId,
                'type'     => $idType
            ]
        );
    }

    /**
     * Get Title and html class based on current detail
     *
     * @return array
     */
    public function getTitleAndClassBasedOnDetail()
    {
        $detail = $this->orderHelper->getGivenValueFromRegistry('current_detail');
        $title = $class = '';

        switch ($detail) {
            case 'order':
                $title = __('Items Ordered');
                $class = 'ordered';
                break;
            case 'shipment':
                $title = __('Shipments');
                $class = 'shipments';
                break;
            case 'invoice':
                $title = __('Invoices');
                $class = 'invoices';
                break;
            case 'creditmemo':
                $title = __('Refunds');
                $class = 'refunds';
                break;
        }

        return [$title, $class];
    }
}
