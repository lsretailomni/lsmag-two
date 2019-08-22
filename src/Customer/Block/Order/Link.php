<?php
namespace Ls\Customer\Block\Order;

/**
 * Class Link
 * @package Ls\Customer\Block\Order
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * @var \Magento\Framework\Registry
     */
    public $_registry;

    /**
     * @var \Ls\Omni\Helper\OrderHelper
     */
    public $orderHelper;

    /**
     * Link constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Ls\Omni\Helper\OrderHelper $orderHelper
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Ls\Omni\Helper\OrderHelper $orderHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->_registry = $registry;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        return $this->_registry->registry('current_order');
    }

    /**
     * Retrieve invoice model instance
     *
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function getMagOrder()
    {
        return $this->_registry->registry('current_mag_order');
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl($this->getPath(), ['order_id' => $this->getOrder()->getId()]);
    }

    /**
     * @param $documentId
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getOrderByDocumentId($documentId)
    {
        return $this->orderHelper->getOrderByDocumentId($documentId);
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function _toHtml()
    {
        $order=$this->getMagOrder();
        if (!empty($order)) {
            if ($this->getKey() == "Invoices" && !($order->hasInvoices())) {
                return '';
            }

            if ($this->getKey() == "Shipments" && !($order->hasShipments())) {
                return '';
            }

            if ($this->hasKey()
                && method_exists($this->getOrder(), 'has' . $this->getKey())
                && !$this->getOrder()->{'has' . $this->getKey()}()
            ) {
                return '';
            }
            return parent::_toHtml();
        } else {
            return '';
        }
    }
}
