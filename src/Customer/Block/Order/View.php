<?php
namespace Ls\Customer\Block\Order;

/**
 * Class View
 * @package Ls\Customer\Block\Order
 */
class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/view.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * View constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }


    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Ls\Omni\Client\Ecommerce\Entity\Order
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
    public function getInvoiceId()
    {
        return $this->coreRegistry->registry('current_invoice_id');
    }


    /**
     * @return mixed
     */
    public function getInvoiceOption()
    {
        return $this->coreRegistry->registry('current_invoice_option');
    }

    /**
     * @return mixed
     */
    public function getShipmentId()
    {
        return $this->coreRegistry->registry('current_shipment_id');
    }

    /**
     * @return mixed
     */
    public function getShipmentOption()
    {
        return $this->coreRegistry->registry('current_shipment_option');
    }

    /**
     * @param object $order
     * @return string
     */
    public function getPrintAllInvoicesUrl($order)
    {
        return $this->getUrl('*/*/printInvoice', ['order_id' => $order->getDocumentId()]);
    }


    /**
     * @param object $order
     * @return string
     */
    public function getPrintAllShipmentUrl($order)
    {
        return $this->getUrl('*/*/printShipment', ['order_id' => $order->getDocumentId()]);
    }

    /**
     * @param object $order
     * @return string
     */
    public function getInvoiceUrl($order)
    {
        return $this->getUrl('*/*/invoice', ['order_id' => $this->getOrder()->getDocumentId()]);
    }

    /**
     * @param object $order
     * @return string
     */
    public function getShipmentUrl($order)
    {
        return $this->getUrl('*/*/shipment', ['order_id' => $this->getOrder()->getDocumentId()]);
    }
}