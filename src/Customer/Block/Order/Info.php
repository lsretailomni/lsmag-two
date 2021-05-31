<?php

namespace Ls\Customer\Block\Order;

use Exception;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session\Proxy;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Sales\Model\OrderRepository;

/**
 * Class Info
 * @package Ls\Customer\Block\Order
 */
class Info extends Template
{
    /**
     * @var CountryFactory
     */
    public $countryFactory;

    /**
     * @var Data
     */
    public $priceHelper;

    /**
     * @var Order Repository
     */
    public $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * @var Proxy
     */
    public $customerSession;

    /**
     * @var OrderHelper
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
     * @var Registry
     */
    public $coreRegistry = null;

    /**
     * @var Context
     */
    public $httpContext;

    /**
     * Info constructor.
     * @param TemplateContext $context
     * @param Registry $registry
     * @param CountryFactory $countryFactory
     * @param Data $priceHelper
     * @param OrderRepository $orderRepository
     * @param OrderHelper $orderHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Proxy $customerSession
     * @param Context $httpContext
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        CountryFactory $countryFactory,
        Data $priceHelper,
        OrderRepository $orderRepository,
        OrderHelper $orderHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Proxy $customerSession,
        Context $httpContext,
        array $data = []
    ) {
        $this->coreRegistry          = $registry;
        $this->countryFactory        = $countryFactory;
        $this->priceHelper           = $priceHelper;
        $this->orderRepository       = $orderRepository;
        $this->orderHelper           = $orderHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession       = $customerSession;
        $this->httpContext           = $httpContext;
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
        $order         = $this->getOrder();
        $shipToAddress = $order->getShipToAddress();
        $address       = '';
        if (!empty($shipToAddress) && !empty($shipToAddress->getCountry())) {
            $address .= $order->getShipToName() ? $order->getShipToName() . '<br/>' : '';
            $address .= $shipToAddress->getAddress1() ? $shipToAddress->getAddress1() . '<br/>' : '';
            $address .= $shipToAddress->getAddress2() ? $shipToAddress->getAddress2() . '<br/>' : '';
            $address .= $shipToAddress->getCity() ? $shipToAddress->getCity() . ', ' : '';
            $address .= $shipToAddress->getStateProvinceRegion() ? $shipToAddress->getStateProvinceRegion() . ', ' : '';
            $address .= $shipToAddress->getPostCode() ? $shipToAddress->getPostCode() . '<br/>' : '';
            $address .= $this->getCountryName($shipToAddress->getCountry()) ?
                $this->getCountryName($shipToAddress->getCountry()) . '<br/>' : '';
            /** TODO update with Address Phone Number */
            /** Removing this field to resolve the Omni 4.13 compatibility
            $address .= $order->getShipToPhoneNumber() ?
                "<a href='tel:" . $order->getShipToPhoneNumber() . "'>" . $order->getShipToPhoneNumber() . '</a>' : '';
             */
        }
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
        if ($this->getOrder()) {
            $this->pageConfig->getTitle()->set(__('Order # %1', $this->getOrder()->getId()));
        }
    }
    // @codingStandardsIgnoreEnd

    /**
     * Retrieve current order model instance
     *
     * @return SalesEntry
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @return Phrase|string
     */
    public function getShippingDescription()
    {
        $status = $this->getOrder()->getClickAndCollectOrder();
        $type   = $this->getOrder()->getIdType();
        if ($type !== DocumentIdType::ORDER) {
            return '';
        }
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
        $methods      = array();
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
                $methods[]       = __('Gift Card');
                $giftCardInfo[0] = $line->getCardNo();
                $giftCardInfo[1] = $line->getAmount();
            } else {
                $methods[] = __('Unknown');
            }
        }
        //TODO when order edit payment available for offline payment we need to change it.
        if (empty($paymentLines->getSalesEntryPayment())) {
            $methods[] = __('Pay At Store');
        }
        return [implode(', ', $methods), $giftCardInfo];
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
        } catch (Exception $e) {
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
