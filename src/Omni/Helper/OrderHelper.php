<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Checkout\Model\Session\Proxy as CheckoutSessionProxy;
use Magento\Customer\Model\Session\Proxy as CustomerSessionProxy;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model;

/**
 * Class OrderHelper
 * @package Ls\Omni\Helper
 */
class OrderHelper extends AbstractHelper
{

    /** @var Model\Order $order */
    public $order;

    /** @var BasketHelper $basketHelper */
    public $basketHelper;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var CustomerSessionProxy
     */
    public $customerSession;

    /**
     * @var CheckoutSessionProxy
     */
    public $checkoutSession;

    /**
     * @var Model\OrderRepository
     */
    public $orderRepository;

    /**
     * OrderHelper constructor.
     * @param Context $context
     * @param Model\Order $order
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param Model\OrderRepository $orderRepository
     * @param CustomerSessionProxy $customerSession
     * @param CheckoutSessionProxy $checkoutSession
     */
    public function __construct(
        Context $context,
        Model\Order $order,
        BasketHelper $basketHelper,
        LoyaltyHelper $loyaltyHelper,
        Model\OrderRepository $orderRepository,
        CustomerSessionProxy $customerSession,
        CheckoutSessionProxy $checkoutSession
    ) {
        parent::__construct($context);
        $this->order = $order;
        $this->basketHelper = $basketHelper;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param $orderId
     * @param Entity\Order $oneListCalculateResponse
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
     */
    public function prepareOrder(Model\Order $order, Entity\Order $oneListCalculateResponse)
    {
        try {
            $isInline = true;
            $storeId = $this->basketHelper->getDefaultWebStore();
            $customerEmail = $order->getCustomerEmail();
            $customerName = $order->getShippingAddress()->getFirstname() .
                ' ' . $order->getShippingAddress()->getLastname();
            $mobileNumber = $order->getShippingAddress()->getTelephone();
            if ($this->customerSession->isLoggedIn()) {
                $contactId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID);
                $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
            } else {
                $contactId = $cardId = '';
            }
            $shippingMethod = $order->getShippingMethod(true);
            //TODO work on condition
            $isClickCollect = $shippingMethod->getData('carrier_code') == 'clickandcollect';
            /** @var Entity\ArrayOfOrderPayment $orderPaymentArrayObject */
            $orderPaymentArrayObject = $this->setOrderPayments($order, $oneListCalculateResponse->getCardId());
            $pointDiscount = $order->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            $order->setCouponCode($this->checkoutSession->getCouponCode());
            $oneListCalculateResponse
                ->setId($order->getIncrementId())
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
                ->setStoreId($storeId);
            if ($isClickCollect) {
                $oneListCalculateResponse->setOrderType(Entity\Enum\OrderType::CLICK_AND_COLLECT);
            } else {
                $oneListCalculateResponse->setOrderType(Entity\Enum\OrderType::SALE);
            }
            $oneListCalculateResponse->setOrderPayments($orderPaymentArrayObject);
            //For click and collect.
            if ($isClickCollect) {
                $oneListCalculateResponse->setCollectLocation($order->getPickupStore());
            }
            $orderLinesArray = $oneListCalculateResponse->getOrderLines()->getOrderLine();
            //For click and collect we need to remove shipment charge orderline
            //For flat shipment it will set the correct shipment value into the order
            $orderLinesArray = $this->updateShippingAmount($orderLinesArray, $order);
            // @codingStandardsIgnoreLine
            $request = new Entity\OrderCreate();
            $oneListCalculateResponse->setOrderLines($orderLinesArray);
            $request->setRequest($oneListCalculateResponse);
            return $request;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $orderLines
     * @param $order
     * @return mixed
     * @throws InvalidEnumException
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
     * @param $orderLine
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
     * @param Entity\OrderCreate $request
     * @return Entity\OrderCreateResponse|Entity\SalesEntry|ResponseInterface
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
            $method = 'setAddress' . strval($i + 1);
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
     * @param Model\Order $order
     * @param $cardId
     * @return Entity\ArrayOfOrderPayment
     * @throws InvalidEnumException
     */
    public function setOrderPayments(Model\Order $order, $cardId)
    {
        $transId = $order->getPayment()->getLastTransId();
        $ccType = $order->getPayment()->getCcType();
        $cardNumber = $order->getPayment()->getCcLast4();
        $paidAmount = $order->getPayment()->getAmountPaid();
        $authorizedAmount = $order->getPayment()->getAmountAuthorized();
        $preApprovedDate = date('Y-m-d', strtotime('+1 years'));

        $orderPaymentArray = [];
        // @codingStandardsIgnoreStart
        $orderPaymentArrayObject = new Entity\ArrayOfOrderPayment();
        // @codingStandardsIgnoreEnd
        //TODO change it to $paymentMethod->isOffline() == false when order edit option available for offline payments.
        if ($order->getPayment()->getMethodInstance()->getCode() != "ls_payment_method_pay_at_store") {
            // @codingStandardsIgnoreStart
            $orderPayment = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
            $orderPayment->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
                ->setCurrencyFactor($order->getBaseToGlobalRate())
                ->setLineNumber('1')
                ->setExternalReference($order->getIncrementId())
                ->setAmount($order->getGrandTotal());
            // For CreditCard/Debit Card payment  use Tender Type 1 for Cards
            if (!empty($transId)) {
                $orderPayment->setTenderType('1');
                $orderPayment->setCardType($ccType);
                $orderPayment->setCardNumber($cardNumber);
                $orderPayment->setTokenNumber($transId);
                if (!empty($paidAmount)) {
                    $orderPayment->setPaymentType(Entity\Enum\PaymentType::PAYMENT);
                } else {
                    if (!empty($authorizedAmount)) {
                        $orderPayment->setPaymentType(Entity\Enum\PaymentType::PRE_AUTHORIZATION);
                    } else {
                        $orderPayment->setPaymentType(Entity\Enum\PaymentType::NONE);
                    }
                }
            } else {
                $orderPayment->setTenderType('0');
            }
            $orderPayment->setPreApprovedValidDate($preApprovedDate);
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
                ->setLineNumber('2')
                ->setCardNumber($cardId)
                ->setExternalReference($order->getIncrementId())
                ->setAmount($order->getLsPointsSpent())
                ->setPreApprovedValidDate($preApprovedDate)
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
                ->setAmount($order->getLsGiftCardAmountUsed())
                ->setLineNumber('3')
                ->setCardNumber($order->getLsGiftCardNo())
                ->setExternalReference($order->getIncrementId())
                ->setPreApprovedValidDate($preApprovedDate)
                ->setTenderType('4');
            $orderPaymentArray[] = $orderPaymentGiftCard;
        }

        return $orderPaymentArrayObject->setOrderPayment($orderPaymentArray);
    }

    /**
     * @return Entity\ArrayOfSalesEntry|Entity\SalesEntriesGetByCardIdResponse|ResponseInterface|null
     */
    public function getCurrentCustomerOrderHistory()
    {
        $response = null;
        $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        if ($cardId == null) {
            return $response;
        }
        // @codingStandardsIgnoreStart
        $request = new Operation\SalesEntriesGetByCardId();
        $orderHistory = new Entity\SalesEntriesGetByCardId();
        // @codingStandardsIgnoreEnd
        $orderHistory->setCardId($cardId);
        try {
            $response = $request->execute($orderHistory);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getSalesEntriesGetByCardIdResult() : $response;
    }

    /**
     * @param $docId
     * @param string $type
     * @return Entity\SalesEntry|Entity\SalesEntryGetResponse|ResponseInterface|null
     * @throws InvalidEnumException
     */
    public function getOrderDetailsAgainstId($docId, $type = DocumentIdType::ORDER)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\SalesEntryGet();
        $order = new Entity\SalesEntryGet();
        $order->setEntryId($docId);
        $order->setType($type);
        // @codingStandardsIgnoreEnd
        try {
            $response = $request->execute($order);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getSalesEntryGetResult() : $response;
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

    /**
     * @param $documentId
     * @return OrderInterface[]
     */
    public function getOrderByDocumentId($documentId)
    {
        try {
            $order      = [];
            $customerId = $this->customerSession->getCustomerId();
            $orderList  = $this->orderRepository->getList(
                $this->basketHelper->searchCriteriaBuilder->
                addFilter('document_id', $documentId, 'eq')->
                addFilter('customer_id', $customerId, 'eq')->create()
            )->getItems();
            if (!empty($orderList)) {
                $order = reset($orderList);
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $order;
    }
}
