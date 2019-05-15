<?php
namespace Ls\Customer\Block\Order;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class History
 * @package Ls\Customer\Block\Order
 */
class History extends \Magento\Sales\Block\Order\History
{
    /**
     * @var \Ls\Omni\Helper\OrderHelper
     */
    public $orderHelper;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /** @var \Ls\Core\Model\LSR @var  */
    public $lsr;

    /**
     * History constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Ls\Omni\Helper\OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Ls\Omni\Helper\OrderHelper $orderHelper,
        PriceCurrencyInterface $priceCurrency,
        \Ls\Core\Model\LSR $LSR,
        array $data = []
    ) {
        $this->orderHelper = $orderHelper;
        $this->priceCurrency = $priceCurrency;
        $this->lsr  =   $LSR;
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }

    /**
     * @return array|bool|\Ls\Omni\Client\Ecommerce\Entity\Order[]|\Magento\Sales\Model\ResourceModel\Order\Collection|null
     */
    public function getOrderHistory()
    {
        /*
        * Adding condition to only process if LSR is enabled.
        */
        if ($this->lsr->isLSR()) {
            $response = null;
            $orders = $this->orderHelper->getCurrentCustomerOrderHistory();
            if ($orders) {
                try {
                    $response = $orders->getOrder();
                } catch (\Exception $e) {
                    $this->_logger->error($e->getMessage());
                }
                if ($response && !is_array($response)) {
                    $obj = $response;
                    $response = [$obj];
                }
            }
            return $response;
        }
        return parent::getOrders();
    }

    /**
     * @param $amount
     * @return float
     */
    public function getFormattedPrice($amount)
    {
        $price = $this->priceCurrency->format($amount, false, 2);
        return $price;
    }

    /**
     * @param $date
     * @return string
     * @throws \Exception
     */
    public function getFormattedDate($date)
    {
        // @codingStandardsIgnoreStart
        $formattedDate = new \DateTime($date);
        // @codingStandardsIgnoreEnd
        $result = $formattedDate->format('d/m/y');
        return $result;
    }

    /**
     * @param object $order
     * @return string
     */
    public function getViewUrl($order)
    {
        /*
        * Adding condition to only process if LSR is enabled.
        */
        if ($this->lsr->isLSR()) {
            return $this->getUrl('customer/order/view', ['order_id' => $order->getDocumentId()]);
        }
        return parent::getViewUrl($order);
    }
}
