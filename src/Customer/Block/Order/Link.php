<?php

namespace Ls\Customer\Block\Order;

use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Block being used for order detail links
 */
class Link extends Current
{
    /**
     * @var Registry
     */
    public $_registry;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * Link constructor.
     * @param Context $context
     * @param DefaultPathInterface $defaultPath
     * @param OrderHelper $orderHelper
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        OrderHelper $orderHelper,
        StoreManagerInterface $storeManager,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->_registry   = $registry;
        $this->context = $context;
        $this->orderHelper = $orderHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve current order model instance
     *
     * @return Order
     */
    private function getOrder()
    {
        return $this->_registry->registry('current_order');
    }

    /**
     * Retrieve invoice model instance
     *
     * @return Invoice
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

        $orderId = $this->orderHelper->getParameterValues($this->getOrder(),"Id");
        $type = $this->orderHelper->getParameterValues($this->getOrder(),"IdType");

        return $this->getUrl($this->getPath(),
            [
                'order_id' => $orderId,
                'type'     => $type
            ]
        );
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function _toHtml()
    {
        $orderId = $this->orderHelper->getParameterValues($this->getOrder(),"Id");
        $order = $this->getMagOrder();
        if (!empty($order)) {
            if ($this->getKey() == "Invoices" && !($order->hasInvoices())) {
                return '';
            }

            if ($this->getKey() == "Shipments" && !($order->hasShipments())) {
                return '';
            }

            if ($this->getKey() == "Creditmemos" && !strpos($this->getCurrentUrl(),"creditmemo") && !($this->orderHelper->hasReturnSale($orderId))) {
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

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentUrl() {
        return $this->storeManager->getStore()->getCurrentUrl();
    }
}
