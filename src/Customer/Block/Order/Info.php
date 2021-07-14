<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session\Proxy;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;

/**
 * Block being used for various sections on order detail
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

    /** @var LSR $lsr */
    public $lsr;

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
     * @param LSR $lsr
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
        LSR $lsr,
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
        $this->lsr                   = $lsr;
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
     * For getting shipping and billing address
     *
     * @param false $isBillingAddress
     * @return string
     */
    public function getFormattedAddress($isBillingAddress = false)
    {
        $order        = $this->getOrder();
        $orderAddress = $order->getShipToAddress();
        if ($isBillingAddress == true) {
            $orderAddress = $order->getContactAddress();
        }
        $address = '';
        if (!empty($orderAddress) && !empty($orderAddress->getCountry())) {
            $address .= $order->getShipToName() ? $order->getShipToName() . '<br/>' : '';
            $address .= $orderAddress->getAddress1() ? $orderAddress->getAddress1() . '<br/>' : '';
            $address .= $orderAddress->getAddress2() ? $orderAddress->getAddress2() . '<br/>' : '';
            $address .= $orderAddress->getCity() ? $orderAddress->getCity() . ', ' : '';
            $address .= $orderAddress->getStateProvinceRegion() ? $orderAddress->getStateProvinceRegion() . ', ' : '';
            $address .= $orderAddress->getPostCode() ? $orderAddress->getPostCode() . '<br/>' : '';
            $address .= $this->getCountryName($orderAddress->getCountry()) ?
                $this->getCountryName($orderAddress->getCountry()) . '<br/>' : '';
            /** TODO update with Address Phone Number */
            /** Removing this field to resolve the Omni 4.13 compatibility
             * $address .= $order->getShipToPhoneNumber() ?
             * "<a href='tel:" . $order->getShipToPhoneNumber() . "'>" . $order->getShipToPhoneNumber() . '</a>' : '';
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
            $this->pageConfig->getTitle()->set(__('%1 # %2', $this->getOrder()->getIdType(), $this->getOrder()->getId()));
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
     * Get selected shipment method for the order, use the one in magento if available
     *
     * @return Phrase|string
     */
    public function getShippingDescription()
    {
        $magentoOrder = $this->getMagOrder();
        $status       = $this->getOrder()->getClickAndCollectOrder();

        if ($magentoOrder) {
            return $magentoOrder->getShippingDescription();
        }

        if ($status) {
            return __('Click and Collect');
        }

        return '';
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
     * Formulating order printing url
     *
     * @param SalesEntry $order
     * @return string
     */
    public function getPrintUrl(SalesEntry $order)
    {
        return $order ? $this->getUrl('customer/order/print', ['order_id' => $order->getId()]) : '';
    }

    /**
     * Formulating reordering url
     *
     * @param $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        return $order ? $this->getUrl('sales/order/reorder', ['order_id' => $order->getId()]) : '';
    }

    /**
     * Formulating order canceling url
     *
     * @param OrderInterface $magentoOrder
     * @param SalesEntry $centralOrder
     * @return string
     */
    public function getCancelUrl(OrderInterface $magentoOrder, SalesEntry $centralOrder)
    {
        return $magentoOrder && $centralOrder ? $this->getUrl(
            'customer/order/cancel',
            [
                'magento_order_id' => $magentoOrder->getId(),
                'central_order_id' => $centralOrder->getId(),
                'id_type'          => $centralOrder->getIdType()
            ]
        ) : '';
    }

    /**
     * Check if order cancellation on frontend is enabled or not
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function orderCancellationOnFrontendIsEnabled()
    {
        return $this->lsr->orderCancellationOnFrontendIsEnabled();
    }

    /**
     * @return mixed
     */
    public function getMagOrder()
    {
        return $this->coreRegistry->registry('current_mag_order');
    }
}
