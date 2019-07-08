<?php

namespace Ls\Omni\Helper;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\Enum;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model;

/**
 * Class OrderHelper
 * @package Ls\Omni\Helper
 */
class OrderHelper extends AbstractHelper
{

    /** @var Model\Order $order */
    public $order;

    /** @var \Ls\Omni\Helper\BasketHelper $basketHelper */
    public $basketHelper;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    public $checkoutSession;

    /**
     * OrderHelper constructor.
     * @param Context $context
     * @param Model\Order $order
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     */
    public function __construct(
        Context $context,
        Model\Order $order,
        BasketHelper $basketHelper,
        LoyaltyHelper $loyaltyHelper,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession
    ) {
        parent::__construct($context);
        $this->order = $order;
        $this->basketHelper = $basketHelper;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param $orderId
     * @param Entity\Order $oneListCalculateResponse
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function placeOrderById($orderId, Entity\Order $oneListCalculateResponse)
    {
        $this->placeOrder(
            $this->prepareOrder($this->order->load($orderId), $oneListCalculateResponse)
        );
    }

    /**
     * @param Model\Order $order
     * @param Entity\Order $oneListCalculateResponse
     * @return Entity\OrderCreate
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function prepareOrder(Model\Order $order, Entity\Order $oneListCalculateResponse)
    {
        $isInline = true;
        $storeId = $this->basketHelper->getDefaultWebStore();
        $anonymousOrder = false;
        $customerEmail = $order->getCustomerEmail();
        $customerName = $order->getShippingAddress()->getFirstname() .
            " " . $order->getShippingAddress()->getLastname();
        $mobileNumber = $order->getShippingAddress()->getTelephone();
        if ($this->customerSession->isLoggedIn()) {
            $contactId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID);
            $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        } else {
            $contactId = $cardId = "";
            $anonymousOrder = true;
        }
        $shippingMethod = $order->getShippingMethod(true);
        //TODO work on condition
        $isClickCollect = $shippingMethod->getData('carrier_code') == 'clickandcollect';
        /** @var Entity\ArrayOfOrderPayment $orderPaymentArrayObject */
        $orderPaymentArrayObject = $this->setOrderPayments($order, $oneListCalculateResponse->getCardId());
        $pointDiscount = $order->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
        $order->setCouponCode($this->checkoutSession->getCouponCode());
        $oneListCalculateResponse
            ->setContactId($contactId)
            ->setCardId($cardId)
            ->setEmail($customerEmail)
            ->setShipToEmail($customerEmail)
            ->setContactName($customerName)
            ->setShipToName($customerName)
            ->setMobileNumber($mobileNumber)
            ->setShipToPhoneNumber($mobileNumber)
            ->setContactAddress($this->convertAddress($order->getBillingAddress()))
            ->setShipToAddress($this->convertAddress($order->getShippingAddress()))
            ->setAnonymousOrder($anonymousOrder)
            ->setClickAndCollectOrder($isClickCollect)
            ->setSourceType(Enum\SourceType::E_COMMERCE)
            ->setStoreId($storeId);
        $oneListCalculateResponse->setOrderPayments($orderPaymentArrayObject);
        //For click and collect.
        if ($isClickCollect) {
            $oneListCalculateResponse->setCollectLocation($order->getPickupStore());
            $oneListCalculateResponse->setShipClickAndCollect(false);
        }
        $orderLines = $oneListCalculateResponse->getOrderLines()->getOrderLine();
        if (!is_array($orderLines)) {
            $orderLinesArray[] = $orderLines;
        } else {
            $orderLinesArray = $orderLines;
        }
        //For click and collect we need to remove shipment charge orderline
        //For flat shipment it will set the correct shipment value into the order
        $orderLinesArray = $this->updateShippingAmount($orderLinesArray, $order);
        // @codingStandardsIgnoreLine
        $request = new Entity\OrderCreate();
        $oneListCalculateResponse->setOrderLines($orderLinesArray);
        $request->setRequest($oneListCalculateResponse);
        return $request;
    }

    /**
     * @param $orderLines
     * @param $order
     * @return mixed
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function updateShippingAmount($orderLines, $order)
    {
        $shipmentFeeId = LSR::LSR_SHIPMENT_ITEM_ID;
        if ($order->getShippingAmount() > 0) {
            // @codingStandardsIgnoreLine
            $shipmentOrderLine = new Entity\OrderLine();
            $shipmentOrderLine->setPrice($order->getShippingAmount())
                ->setNetPrice($order->getBaseShippingAmount())
                ->setNetAmount($order->getBaseShippingAmount())
                ->setAmount($order->getBaseShippingAmount())
                ->setItemId($shipmentFeeId)
                ->setLineType(Entity\Enum\LineType::ITEM)
                ->setQuantity(1)
                ->setQuantityToInvoice(1)
                ->setDiscountAmount($order->getShippingDiscountAmount());
            array_push($orderLines, $shipmentOrderLine);
        }
        return $orderLines;
    }

    /**
     * @param $line
     * @param $order
     */
    public function setSpecialPropertiesForShipmentLine(&$orderLine, $order)
    {
        $orderLine->setPrice($order->getShippingAmount())
            ->setNetPrice($order->getBaseShippingAmount())
            ->setQuantity(1)
            ->setDiscountAmount($order->getShippingDiscountAmount());
    }

    /**
     * Place the Order directly
     * @param Entity\OrderCreate $request
     * @return Entity\OrderCreateResponse|\Ls\Omni\Client\IResponse
     */
    public function placeOrder(Entity\OrderCreate $request)
    {
        $response = null;
        // @codingStandardsIgnoreLine
        $operation = new Operation\OrderCreate();
        $response = $operation->execute($request);

        // @codingStandardsIgnoreLine
        return $response ? $response->getResult() : $response;
    }

    /**
     * @param Model\Order\Address $magentoAddress
     * @return Entity\Address
     */
    public function convertAddress(Model\Order\Address $magentoAddress)
    {
        // @codingStandardsIgnoreLine
        $omniAddress = new Entity\Address();
        foreach ($magentoAddress->getStreet() as $i => $street) {
            // @codingStandardsIgnoreLine
            //TODO support multiple line address more than 3.
            // stopping the address for multiple street lines, only accepting Address1 and Address2.
            if ($i > 1) {
                break;
            }
            // @codingStandardsIgnoreLine
            $method = "setAddress" . strval($i + 1);
            $omniAddress->$method($street);
        }
        $region = substr($magentoAddress->getRegion(), 0, 30);
        $omniAddress
            ->setCity($magentoAddress->getCity())
            ->setCountry($magentoAddress->getCountryId())
            ->setStateProvinceRegion($region)
            ->setPostCode($magentoAddress->getPostcode());

        return $omniAddress;
    }

    /**
     * Please use this funciton to put all condition for different Order Payments:
     * @param Model\Order $order
     * @param $cardId
     * @return Entity\ArrayOfOrderPayment
     */
    public function setOrderPayments(Model\Order $order, $cardId)
    {
        $transId = $order->getPayment()->getCcTransId();
        $ccType = $order->getPayment()->getCcType();
        $cardNumber = $order->getPayment()->getCcLast4();

        $orderPaymentArray = [];
        // @codingStandardsIgnoreStart
        $orderPaymentArrayObject = new Entity\ArrayOfOrderPayment();
        // @codingStandardsIgnoreEnd

        if ($order->getPayment()->getMethodInstance()->getCode() != "ls_payment_method_pay_at_store") {
            // @codingStandardsIgnoreStart
            $orderPayment = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
            $orderPayment->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
                ->setCurrencyFactor($order->getBaseToGlobalRate())
                ->setFinalizedAmount(0)
                ->setLineNumber('1')
                ->setOrderId($order->getIncrementId())
                ->setPreApprovedAmount($order->getGrandTotal());
            // For CreditCard/Debit Card payment  use Tender Type 1 for Cards
            if ($ccType != "" and $ccType != null) {
                $orderPayment->setTenderType('1');
                $orderPayment->setCardType($ccType);
                $orderPayment->setCardNumber($cardNumber);
                $orderPayment->setAuthorisationCode($transId);
            } else {
                $orderPayment->setTenderType('0');
            }
            $orderPaymentArray[] = $orderPayment;
        }

        // @codingStandardsIgnoreLine
        /*
         * Not Supporting at the moment, so all payment methods will be offline,
        if($order->getPayment()->getMethodInstance()->getCode() == 'cashondelivery'
        || $order->getPayment()->getMethodInstance()->getCode() == 'checkmo'){
            // 0 Mean cash.
        }
         *
         */

        if ($order->getLsPointsSpent()) {
            $pointRate = $this->loyaltyHelper->getPointRate();
            // @codingStandardsIgnoreStart
            $orderPaymentLoyalty = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
            $orderPaymentLoyalty->setCurrencyCode('LOY')
                ->setCurrencyFactor($pointRate)
                ->setFinalizedAmount('0')
                ->setLineNumber('2')
                ->setCardNumber($cardId)
                ->setOrderId($order->getIncrementId())
                ->setPreApprovedAmount($order->getLsPointsSpent())
                ->setTenderType('3');
            $orderPaymentArray[] = $orderPaymentLoyalty;
        }
        if ($order->getLsGiftCardAmountUsed()) {
            // @codingStandardsIgnoreStart
            $orderPaymentGiftCard = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
            $orderPaymentGiftCard
                ->setCurrencyFactor(1)
                ->setFinalizedAmount('0')
                ->setLineNumber('3')
                ->setCardNumber($order->getLsGiftCardNo())
                ->setOrderId($order->getIncrementId())
                ->setPreApprovedAmount($order->getLsGiftCardAmountUsed())
                ->setTenderType('4');
            $orderPaymentArray[] = $orderPaymentGiftCard;
        }

        return $orderPaymentArrayObject->setOrderPayment($orderPaymentArray);
    }

    /**
     * @return Entity\ArrayOfOrder|Entity\OrderHistoryByContactIdResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getCurrentCustomerOrderHistory()
    {
        $response = null;
        $contactId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID);
        // @codingStandardsIgnoreStart
        $request = new Operation\OrderHistoryByContactId();
        $orderHistory = new Entity\OrderHistoryByContactId();
        // @codingStandardsIgnoreEnd
        $orderHistory->setContactId($contactId)->setIncludeLines(true)->setIncludeTransactions(true);
        try {
            $response = $request->execute($orderHistory);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getOrderHistoryByContactIdResult() : $response;
    }

    /**
     * @param $orderId
     * @return Entity\Order|Entity\OrderGetByIdResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getOrderDetailsAgainstId($orderId)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\OrderGetById();
        $order = new Entity\OrderGetById();
        $order->setId($orderId)->setIncludeLines(true);
        // @codingStandardsIgnoreEnd
        try {
            $response = $request->execute($order);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getOrderGetByIdResult() : $response;
    }

    /**
     * @param $order
     * @return bool
     */
    public function isAuthorizedForOrder($order)
    {
        $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        $orderCardId = $order->getCardId();
        if ($cardId == $orderCardId) {
            return true;
        }
        return false;
    }
}
