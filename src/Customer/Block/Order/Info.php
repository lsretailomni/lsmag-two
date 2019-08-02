<?php

namespace Ls\Customer\Block\Order;

use \Ls\Omni\Client\Ecommerce\Entity\Order;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as TemplateContext;

/**
 * Class Info
 * @package Ls\Customer\Block\Order
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    public $countryFactory;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;

    /**
     * @var Order Repository
     */
    public $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /**
     * @var \Ls\Omni\Helper\OrderHelper
     */
    public $orderHelper;

    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/info.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    public $httpContext;

    /**
     * Info constructor.
     * @param TemplateContext $context
     * @param Registry $registry
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Ls\Omni\Helper\OrderHelper $orderHelper
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Ls\Omni\Helper\OrderHelper $orderHelper,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->countryFactory = $countryFactory;
        $this->priceHelper = $priceHelper;
        $this->orderRepository = $orderRepository;
        $this->orderHelper = $orderHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
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
     * @return string
     */
    public function getFormattedAddress()
    {
        $order = $this->getOrder();
        $shipToAddress = $order->getShipToAddress();
        $address = "";
        $tmp = "";
        $tmp = $order->getShipToName() ? $order->getShipToName() . "<br/>" : "";
        $address .= $tmp;
        $tmp = "";
        $tmp = $shipToAddress->getAddress1() ? $shipToAddress->getAddress1() . "<br/>" : "";
        $address .= $tmp;
        $tmp = "";
        $tmp = $shipToAddress->getAddress2() ? $shipToAddress->getAddress2() . "<br/>" : "";
        $address .= $tmp;
        $tmp = "";
        $tmp = $shipToAddress->getCity() ? $shipToAddress->getCity() . ", " : "";
        $address .= $tmp;
        $tmp = "";
        $tmp = $shipToAddress->getStateProvinceRegion() ? $shipToAddress->getStateProvinceRegion() . ", " : "";
        $address .= $tmp;
        $tmp = "";
        $tmp = $shipToAddress->getPostCode() ? $shipToAddress->getPostCode() . "<br/>" : "";
        $address .= $tmp;
        $tmp = "";
        $tmp = $this->getCountryName($shipToAddress->getCountry()) ?
            $this->getCountryName($shipToAddress->getCountry()) . "<br/>" : "";
        $address .= $tmp;
        $tmp = "";
        $tmp = $order->getShipToPhoneNumber() ?
            "<a href='tel:" . $order->getShipToPhoneNumber() . "'>" . $order->getShipToPhoneNumber() . "</a>" : "";
        $address .= $tmp;
        return $address;
    }

    /**
     * @param $countryCode
     * @return string
     */
    public function getCountryName($countryCode)
    {
        $country = $this->countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     * @return void
     */
    // @codingStandardsIgnoreStart
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order # %1', $this->getOrder()->getId()));
    }
    // @codingStandardsIgnoreEnd

    /**
     * Retrieve current order model instance
     *
     * @return \Ls\Omni\Client\Ecommerce\Entity\SalesEntry
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getShippingDescription()
    {
        $status = $this->getOrder()->getClickAndCollectOrder();
        if ($status) {
            return __('Click and Collect');
        } else {
            return __('Flat Rate - Fixed');
        }
    }

    /**
     * @return array
     */
    public function getPaymentDescription()
    {
        // @codingStandardsIgnoreStart
        $paymentLines = $this->getOrder()->getPayments();
        $methods = array();
        $giftCardInfo = array();
        // @codingStandardsIgnoreEnd
        foreach ($paymentLines as $line) {
            if ($line->getTenderType() == '0') {
                $methods[] = __('Cash');
            } elseif ($line->getTenderType() == '1') {
                $methods[] = __('Card');
            } elseif ($line->getTenderType() == '2') {
                $methods[] = __('Coupon');
            } elseif ($line->getTenderType() == '3') {
                $methods[] = __('Loyalty Points');
            } elseif ($line->getTenderType() == '4') {
                $methods[] = __('Gift Card');
                //TODO Need to change Card Code
                $giftCardInfo[0] = $line->getTenderType();
                $giftCardInfo[1] = $line->getAmount();
            } else {
                $methods[] = __('Unknown');
            }
        }
        if(empty($paymentLines)){
            $methods[] = __('Pay At Store');
        }
        return[implode(', ', $methods),$giftCardInfo];
    }

    /**
     * @param $points
     * @return string
     */
    public function getFormattedLoyaltyPoints($points)
    {
        $points = number_format((float)$points, 2, '.', '');
        return $points;
    }

    /**
     * @param $points
     * @return string
     */
    public function getGiftCardFormattedPrice($giftCardAmount)
    {
        return $this->priceHelper->currency($giftCardAmount, true, false);
    }

    /**
     * Get url for printing order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getPrintUrl($order)
    {
        if ($order->getId() != null) {
            return $this->getUrl('customer/order/print', ['order_id' => $order->getId()]);
        }
    }

    /**
     * @param $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        try {
            if ($order->getDocumentId() != null) {
                return $this->getUrl('sales/order/reorder', ['order_id' => $order->getEntityId()]);
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @return mixed
     */
    public function getMagOrder()
    {
        return $this->coreRegistry->registry('current_mag_order');
    }
}
