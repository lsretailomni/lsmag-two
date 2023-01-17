<?php

namespace Ls\Customer\Block\Order;

use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Block being used for order detail links
 */
class Link extends Current
{
    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * Link constructor.
     * @param Context $context
     * @param DefaultPathInterface $defaultPath
     * @param OrderHelper $orderHelper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        OrderHelper $orderHelper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->orderHelper = $orderHelper;
        $this->storeManager = $storeManager;
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
     * Retrieve invoice model instance
     *
     * @return Invoice
     */
    public function getMagOrder()
    {
        return $this->orderHelper->getGivenValueFromRegistry('current_mag_order');
    }

    /**
     * Get order view URL
     * @inheritdoc
     *
     * @return string
     */
    public function getHref()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        if ($this->getPath() == 'customer/order/view') {
            $type = DocumentIdType::ORDER;
        } else {
            $type = DocumentIdType::RECEIPT;
        }

        return $this->getUrl(
            $this->getPath(),
            [
                'order_id' => $orderId,
                'type'     => $type
            ]
        );
    }

    /**
     * @inheritDoc
     *
     * @return string
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    protected function _toHtml()
    {
        $order = $this->getMagOrder();
        if (!empty($order)) {
            if ($this->getKey() == "Invoices" && !($order->hasInvoices())) {
                return '';
            }

            if ($this->getKey() == "Shipments" && !($order->hasShipments())) {
                return '';
            }

            if ($this->getKey() == "Creditmemos" &&
                !$this->orderHelper->getGivenValueFromRegistry('has_return_sales')
                && !strpos($this->getCurrentUrl(), "creditmemo")
            ) {
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
     * Get current store URL
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentUrl()
    {
        return $this->storeManager->getStore()->getCurrentUrl();
    }
}
