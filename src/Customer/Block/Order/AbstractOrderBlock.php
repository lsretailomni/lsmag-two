<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data as DataHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderRepository;

class AbstractOrderBlock extends Template
{
    /**
     * @param Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     * @param DataHelper $dataHelper
     * @param PriceHelper $priceHelper
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerSession $customerSession
     * @param CountryFactory $countryFactory
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Http $request
     * @param array $data
     */
    public function __construct(
        Context $context,
        public PriceCurrencyInterface $priceCurrency,
        public LoyaltyHelper $loyaltyHelper,
        public LSR $lsr,
        public OrderHelper $orderHelper,
        public DataHelper $dataHelper,
        public PriceHelper $priceHelper,
        public OrderRepository $orderRepository,
        public SearchCriteriaBuilder $searchCriteriaBuilder,
        public CustomerSession $customerSession,
        public CountryFactory $countryFactory,
        public \Magento\Framework\App\Http\Context $httpContext,
        public Http $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @param $all
     * @return false|mixed|null
     */
    public function getOrder($all = false)
    {
        if (!$all) {
            if ($this->getData('current_order')) {
                return $this->getData('current_order');
            }
        }

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
        $reqType = $this->getRequest()->getParam('type');

        $params ['order_id'] = $order->getDocumentId();

        if ($reqType) {
            $params ['order_id'] = $this->getRequest()->getParam('order_id');
            $params ['type']     = $reqType;
        }

        return $this->getUrl('*/*/printInvoice', $params);
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
        $title  = $class = '';

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

    public function getHeading()
    {
        $detail = $this->orderHelper->getGivenValueFromRegistry('current_detail');
        $orderId = $this->request->getParam('order_id');
        $newOrderId = $this->request->getParam('new_order_id');
        $heading = '';
        switch ($detail) {
            case 'order':
                break;
            case 'shipment':
                $heading = __('Shipment # %1', $orderId);
                break;
            case 'invoice':
                $heading = __('Invoice # %1', $orderId);
                break;
            case 'creditmemo':
                $orderId = !empty($newOrderId) ? current($newOrderId) : $orderId;
                $heading = __('Credit Memo # %1', $orderId);
                break;
        }

        return $heading;
    }

    /**
     * Return back title for logged in and guest users
     *
     * @return \Magento\Framework\Phrase
     */
    public function getBackTitle()
    {
        if ($this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
            return __('Back to My Orders');
        }
        return __('View Another Order');
    }
}
