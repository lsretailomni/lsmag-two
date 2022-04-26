<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\PaymentType;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Helper\Data as DataHelper;
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
     * @var DataHelper
     */
    public $dataHelper;

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
     * @param DataHelper $dataHelper
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
        DataHelper $dataHelper,
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
        $this->dataHelper            = $dataHelper;
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
        $order = $this->getOrder();
        if ($isBillingAddress == true) {
            $orderAddress = $this->orderHelper->getParameterValues($order,"ContactAddress");
        } else {
            $orderAddress = $this->orderHelper->getParameterValues($order,"ShipToAddress");
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
            $address .= $orderAddress->getPhoneNumber() ?
                "<a href='tel:" . $orderAddress->getPhoneNumber() . "'>" . $orderAddress->getPhoneNumber() . '</a>' : '';

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
        $order           = $this->getOrder();
        $customerOrderNo = null;
        if ($order) {

            $orderId = $this->orderHelper->getParameterValues($order,"CustomerOrderNo") ?: $this->orderHelper->getParameterValues($order,"Id");
            $customerOrderNo = $this->orderHelper->getParameterValues($order,"CustomerOrderNo");

            if (!empty($customerOrderNo)) {
                $type = __('Order');
            } else {
                $type = $order->getIdType();
            }
            $this->pageConfig->getTitle()->set(__('%1 # %2', $type, $orderId));
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
     * @return mixed
     */
    public function getOrderStatus()
    {
        return $this->orderHelper->getParameterValues($this->getOrder(),"Status");
    }

    public function getClickAndCollectOrder()
    {
        return $this->orderHelper->getParameterValues($this->getOrder(),"ClickAndCollectOrder");
    }

    /**
     * @return string|null
     */
    public function getDocRegistraionTime()
    {
        return $this->orderHelper->getParameterValues($this->getOrder(),"DocumentRegTime");
    }

    /**
     * Get selected shipment method for the order, use the one in magento if available
     *
     * @return Phrase|string
     */
    public function getShippingDescription()
    {
        $magentoOrder = $this->getMagOrder();
        $status       = $this->getClickAndCollectOrder();

        if ($magentoOrder) {
            return $magentoOrder->getShippingDescription();
        }

        if ($status) {
            return __('Click and Collect');
        }

        return '';
    }

    /**
     * Return Requested Delivery Date
     *
     * @return string|null
     */
    public function getRequestedDeliveryDate()
    {
        $format = $this->lsr->getStoreConfig(LSR::PICKUP_DATE_FORMAT);
        $requestedDeliveryDate = $this->getOrder()->getRequestedDeliveryDate();
        if($requestedDeliveryDate!='0001-01-01T00:00:00') {
            return $this->orderHelper->getDateTimeObject()->date($format, $this->getOrder()->getRequestedDeliveryDate());
        }

        return null;
    }

    /**
     * DEV Notes:
     * 1st entry is for normal tender type
     * 2nd entry is specific for Giftcard.
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPaymentDescription()
    {
        $paymentLines      = $this->getOrderPayments();
        $methods           = $giftCardInfo = [];
        $tenderTypeMapping = $this->dataHelper->getTenderTypesPaymentMapping();
        foreach ($paymentLines as $line) {
            /**
             * Payments line can include multiple payment types
             * i-e Refunds etc, but we only need to show Payment Type
             * whose type == Payment and Pre Authorization
             */
            if ($line->getType() === PaymentType::PAYMENT || $line->getType() === PaymentType::PRE_AUTHORIZATION) {
                $tenderTypeId = $line->getTenderType();
                if (array_key_exists($tenderTypeId, $tenderTypeMapping)) {
                    $method    = $tenderTypeMapping[$tenderTypeId];
                    $methods[] = __($method);
                    if (!empty($line->getCardNo())) {
                        $giftCardTenderId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
                        if ($giftCardTenderId == $tenderTypeId) {
                            $giftCardInfo[0] = $line->getCardNo();
                            $giftCardInfo[1] = $line->getAmount();
                        }
                    }
                } else {
                    $methods[] = __('Unknown');
                }
            }
        }

        $methods = array_unique($methods);
        if (empty($paymentLines->getSalesEntryPayment())) {
            $magOrder = $this->getMagOrder();
            if ($magOrder != null) {
                $magPaymentMethod = $this->getMagOrder()->getPayment()->getMethodInstance()->getTitle();
                $methods[]        = $magPaymentMethod;
            }
        }

        return [implode(', ', $methods), $giftCardInfo];
    }

    /**
     * @return mixed
     */
    public function getOrderPayments()
    {
        return $this->orderHelper->getParameterValues($this->getOrder(),"Payments");
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
     * @param $order
     * @return string
     */
    public function getPrintUrl($order)
    {

        $typeId = $this->orderHelper->getParameterValues($order,"IdType");
        $orderId = $this->getRequest()->getParam('order_id');
        return $order ? $this->getUrl(
            'customer/order/print',
            ['order_id' => $order->getId(), 'type' => $order->getIdType()]

        ) : '';
    }

    /**
     * Formulating reordering url
     *
     * @param $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        $orderId = $this->getRequest()->getParam('order_id');
        return $order ? $this->getUrl('sales/order/reorder', ['order_id' => $orderId]) : '';
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
