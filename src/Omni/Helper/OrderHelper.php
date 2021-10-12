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
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Useful helper functions for order
 *
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

    /** @var  LSR $lsr */
    public $lsr;

    /**
     * @var Order
     */
    public $orderResourceModel;

    /**
     * @var array
     */
    public $tendertypesArray = [];

    /**
     * @var Json
     */
    public $json;

    /**
     * OrderHelper constructor.
     * @param Context $context
     * @param Model\Order $order
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param Model\OrderRepository $orderRepository
     * @param CustomerSessionProxy $customerSession
     * @param CheckoutSessionProxy $checkoutSession
     * @param LSR $lsr
     * @param Order $orderResourceModel
     * @param Json $json
     */
    public function __construct(
        Context $context,
        Model\Order $order,
        BasketHelper $basketHelper,
        LoyaltyHelper $loyaltyHelper,
        Model\OrderRepository $orderRepository,
        CustomerSessionProxy $customerSession,
        CheckoutSessionProxy $checkoutSession,
        LSR $lsr,
        Order $orderResourceModel,
        Json $json
    ) {
        parent::__construct($context);
        $this->order              = $order;
        $this->basketHelper       = $basketHelper;
        $this->loyaltyHelper      = $loyaltyHelper;
        $this->orderRepository    = $orderRepository;
        $this->customerSession    = $customerSession;
        $this->checkoutSession    = $checkoutSession;
        $this->lsr                = $lsr;
        $this->orderResourceModel = $orderResourceModel;
        $this->json               = $json;
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
     * This function is overriding in hospitality module
     * @param Model\Order $order
     * @param $oneListCalculateResponse
     * @return Entity\OrderCreate
     */
    public function prepareOrder(Model\Order $order, $oneListCalculateResponse)
    {
        try {
            $storeId       = $oneListCalculateResponse->getStoreId();
            $cardId        = $oneListCalculateResponse->getCardId();
            $customerEmail = $order->getCustomerEmail();
            $customerName  = $order->getBillingAddress()->getFirstname() . ' ' .
                $order->getBillingAddress()->getLastname();

            if ($order->getShippingAddress()) {
                $shipToName = $order->getShippingAddress()->getFirstname() . ' ' .
                    $order->getShippingAddress()->getLastname();
            } else {
                $shipToName = $customerName;
            }

            if ($this->customerSession->isLoggedIn()) {
                $contactId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID);
            } else {
                $contactId = '';
            }
            $shippingMethod = $order->getShippingMethod(true);
            //TODO work on condition
            $isClickCollect = false;
            $carrierCode    = '';
            $method         = '';

            if ($shippingMethod !== null) {
                $carrierCode    = $shippingMethod->getData('carrier_code');
                $method         = $shippingMethod->getData('method');
                $isClickCollect = $carrierCode == 'clickandcollect';
            }

            /** Entity\ArrayOfOrderPayment $orderPaymentArrayObject */
            $orderPaymentArrayObject = $this->setOrderPayments($order, $cardId);
            if (!empty($this->checkoutSession->getCouponCode())) {
                $order->setCouponCode($this->checkoutSession->getCouponCode());
            }

            //if the shipping address is empty, we use the contact address as shipping address.
            $contactAddress = $order->getBillingAddress() ? $this->convertAddress($order->getBillingAddress()) : null;
            $shipToAddress  = $order->getShippingAddress() ? $this->convertAddress($order->getShippingAddress()) :
                $contactAddress;

            $oneListCalculateResponse
                ->setId($order->getIncrementId())
                ->setContactId($contactId)
                ->setCardId($cardId)
                ->setEmail($customerEmail)
                ->setShipToEmail($customerEmail)
                ->setContactName($customerName)
                ->setShipToName($shipToName)
                ->setContactAddress($contactAddress)
                ->setShipToAddress($shipToAddress)
                ->setStoreId($storeId);
            if ($isClickCollect) {
                $oneListCalculateResponse->setOrderType(Entity\Enum\OrderType::CLICK_AND_COLLECT);
            } else {
                $oneListCalculateResponse->setOrderType(Entity\Enum\OrderType::SALE);
                //TODO need to fix the length issue once LS Central allow more then 10 characters.
                $oneListCalculateResponse->setShippingAgentCode(substr($carrierCode,0,10));
                $oneListCalculateResponse->setShippingAgentServiceCode(substr($method,0,10));
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
        $shipmentFeeId      = $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID, $order->getStoreId());
        $shipmentTaxPercent = $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_TAX, $order->getStoreId());
        $shippingAmount     = $order->getShippingAmount();
        if ($shippingAmount > 0) {
            $netPriceFormula = 1 + $shipmentTaxPercent / 100;
            $netPrice        = $shippingAmount / $netPriceFormula;
            $taxAmount       = number_format(($shippingAmount - $netPrice), 2);
            // @codingStandardsIgnoreLine
            $shipmentOrderLine = new Entity\OrderLine();
            $shipmentOrderLine->setPrice($shippingAmount)
                ->setAmount($shippingAmount)
                ->setNetPrice($netPrice)
                ->setNetAmount($netPrice)
                ->setTaxAmount($taxAmount)
                ->setItemId($shipmentFeeId)
                ->setLineType(Entity\Enum\LineType::ITEM)
                ->setQuantity(1)
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
     * This function is overriding in hospitality module
     * @param $request
     * @return Entity\OrderCreateResponse|ResponseInterface
     */
    public function placeOrder($request)
    {
        $response = null;
        // @codingStandardsIgnoreLine
        $operation = new Operation\OrderCreate();
        $response  = $operation->execute($request);
        // @codingStandardsIgnoreLine
        return $response;
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
            ->setPostCode($magentoAddress->getPostcode())
            ->setPhoneNumber($magentoAddress->getTelephone());

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
        $transId          = $order->getPayment()->getLastTransId();
        $ccType           = substr($order->getPayment()->getCcType(), 0, 10);
        $cardNumber       = $order->getPayment()->getCcLast4();
        $paidAmount       = $order->getPayment()->getAmountPaid();
        $authorizedAmount = $order->getPayment()->getAmountAuthorized();
        $preApprovedDate  = date('Y-m-d', strtotime('+1 years'));

        $orderPaymentArray = [];
        // @codingStandardsIgnoreStart
        $orderPaymentArrayObject = new Entity\ArrayOfOrderPayment();
        // @codingStandardsIgnoreEnd
        //TODO change it to $paymentMethod->isOffline() == false when order edit option available for offline payments.
        $paymentCode  = $order->getPayment()->getMethodInstance()->getCode();
        $tenderTypeId = $this->getPaymentTenderTypeId($paymentCode);

        $noOrderPayment = ['ls_payment_method_pay_at_store', 'free'];

        if (!in_array($paymentCode, $noOrderPayment)) {
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
            }

            $orderPayment->setTenderType($tenderTypeId);
            $orderPayment->setPreApprovedValidDate($preApprovedDate);
            $orderPaymentArray[] = $orderPayment;
        }

        if ($order->getLsPointsSpent()) {
            $tenderTypeId = $this->getPaymentTenderTypeId(LSR::LS_LOYALTYPOINTS_TENDER_TYPE);
            $pointRate    = $this->loyaltyHelper->getPointRate();
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
                ->setTenderType($tenderTypeId);
            $orderPaymentArray[] = $orderPaymentLoyalty;
        }
        if ($order->getLsGiftCardAmountUsed()) {
            $tenderTypeId = $this->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
            // @codingStandardsIgnoreStart
            $orderPaymentGiftCard = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
            $orderPaymentGiftCard
                ->setCurrencyFactor(1)
                ->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
                ->setAmount($order->getLsGiftCardAmountUsed())
                ->setLineNumber('3')
                ->setCardNumber($order->getLsGiftCardNo())
                ->setExternalReference($order->getIncrementId())
                ->setPreApprovedValidDate($preApprovedDate)
                ->setTenderType($tenderTypeId);
            $orderPaymentArray[] = $orderPaymentGiftCard;
        }

        return $orderPaymentArrayObject->setOrderPayment($orderPaymentArray);
    }

    /**
     * @param null $maxNumberOfEntries
     * @return Entity\ArrayOfSalesEntry|Entity\SalesEntriesGetByCardIdResponse|ResponseInterface|null
     */
    public function getCurrentCustomerOrderHistory($maxNumberOfEntries = null)
    {
        $response = null;
        $cardId   = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        if ($cardId == null) {
            return $response;
        }
        // @codingStandardsIgnoreStart
        $request      = new Operation\SalesEntriesGetByCardId();
        $orderHistory = new Entity\SalesEntriesGetByCardId();
        // @codingStandardsIgnoreEnd
        $orderHistory->setCardId($cardId);
        if (!empty($maxNumberOfEntries)) {
            $orderHistory->setMaxNumberOfEntries($maxNumberOfEntries);
        }
        try {
            $response = $request->execute($orderHistory);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getSalesEntriesGetByCardIdResult() : $response;
    }

    /**
     * This function is overriding in hospitality module
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
        $order   = new Entity\SalesEntryGet();
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
        $cardId      = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        $orderCardId = $order->getCardId();
        if ($cardId == $orderCardId) {
            return true;
        }
        return false;
    }

    /**
     * Get respective magento order given commerce service sales entry
     *
     * @param $salesEntry
     * @return array|OrderInterface
     */
    public function getOrderByDocumentId($salesEntry)
    {
        $order = [];
        try {
            $documentId = $this->getDocumentIdGivenSalesEntry($salesEntry);

            if (!empty($documentId)) {
                $customerId = $this->customerSession->getCustomerId();
                $orderList  = $this->orderRepository->getList(
                    $this->basketHelper->getSearchCriteriaBuilder()->
                    addFilter('document_id', $documentId, 'eq')->
                    addFilter('customer_id', $customerId, 'eq')->create()
                )->getItems();
                if (!empty($orderList)) {
                    $order = reset($orderList);
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $order;
    }

    /**
     * Return orders from Magento
     *
     * @param null $storeId
     * @param int $pageSize
     * @param bool $filterOptions
     * @param int $customerId
     * @param null $sortOrder
     * @return OrderInterface[]|null
     */
    public function getOrders(
        $storeId = null,
        $pageSize = -1,
        $filterOptions = true,
        $customerId = 0,
        $sortOrder = null
    ) {
        $orders = null;
        try {
            $criteriaBuilder = $this->basketHelper->getSearchCriteriaBuilder();

            if ($filterOptions == true) {
                $criteriaBuilder->addFilter('status', 'canceled', 'neq');
                $criteriaBuilder->addFilter('document_id', null, 'null');
            }
            if ($customerId) {
                $criteriaBuilder->addFilter('customer_id', $customerId, 'eq');
            }
            if ($storeId) {
                $criteriaBuilder = $criteriaBuilder->addFilter('store_id', $storeId, 'eq');
            }
            if ($sortOrder) {
                $criteriaBuilder = $criteriaBuilder->setSortOrders([$sortOrder]);
            }

            $searchCriteria =
                $criteriaBuilder->setPageSize($pageSize)
                    ->create();

            $orders = $this->orderRepository->getList($searchCriteria)->getItems();

            return $orders;

        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $orders;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getActiveWebStore()
    {
        return $this->lsr->getActiveWebStore();
    }

    /**
     * @param $order
     * @throws AlreadyExistsException
     */
    public function disasterRecoveryHandler($order)
    {
        $this->_logger->critical(__('Something terrible happened while placing order %1', $order->getIncrementId()));
        $order->addCommentToStatusHistory(__('The service is currently unavailable. Please try again later.'));
        try {
            $this->orderResourceModel->save($order);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        $this->basketHelper->unSetLastDocumentId();
        $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
    }

    /**
     * @param $adyenResponse
     * @param $order
     * @return OrderInterface|mixed
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function setAdyenParameters($adyenResponse, $order)
    {
        if (!empty($adyenResponse)) {
            if (isset($adyenResponse['pspReference'])) {
                $order->getPayment()->setLastTransId($adyenResponse['pspReference']);
                $order->getPayment()->setCcTransId($adyenResponse['pspReference']);
            }
            if (isset($adyenResponse['paymentMethod'])) {
                $order->getPayment()->setCcType($adyenResponse['paymentMethod']);
            }
            if (isset($adyenResponse['authResult'])) {
                $order->getPayment()->setCcStatus($adyenResponse['authResult']);
            }
            $this->orderRepository->save($order);
            $order = $this->orderRepository->get($order->getEntityId());
        }
        return $order;
    }

    /**
     * This function is overriding in hospitality module
     *
     * For cancelling the order in LS central
     * @param $documentId
     * @param $storeId
     * @return string
     */
    public function orderCancel($documentId, $storeId)
    {
        $response = null;
        $request  = new Entity\OrderCancel();
        $request->setOrderId($documentId);
        $request->setStoreId($storeId);
        $request->setUserId("");
        $operation = new Operation\OrderCancel();
        try {
            $response = $operation->execute($request);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Get respective document_id given commerce service sales entry
     *
     * @param $salesEntry
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getDocumentIdGivenSalesEntry($salesEntry)
    {
        // This is to support backward compatibility of Omni
        if (version_compare($this->lsr->getOmniVersion(), '4.6.0', '>')) {
            return $salesEntry->getCustomerOrderNo();
        }

        return $salesEntry->getId();
    }

    /**
     * Get configuration value for tender type
     *
     * @return mixed
     */
    public function getPaymentTenderMapping()
    {
        if ($this->tendertypesArray) {
            return $this->tendertypesArray;
        }
        $paymentTenderTypesArray = $this->lsr->getStoreConfig(
            LSR::LSR_PAYMENT_TENDER_TYPE_MAPPING,
            $this->lsr->getCurrentStoreId()
        );

        if (!is_array($paymentTenderTypesArray)) {
            $paymentTenderTypesArray = $this->json->unserialize($paymentTenderTypesArray);
        }

        foreach ($paymentTenderTypesArray as $row) {
            if (isset($row['tender_type'])) {
                $this->tendertypesArray[$row['payment_method']] = $row['tender_type'];
            }
        }

        return $this->tendertypesArray;
    }

    /**
     * Get Tender type id by payment code
     *
     * @param $code
     * @return int|mixed
     */
    public function getPaymentTenderTypeId($code)
    {
        $tenderTypeId            = 0;
        $paymentTenderTypesArray = $this->getPaymentTenderMapping();
        if (array_key_exists($code, $paymentTenderTypesArray)) {
            $tenderTypeId = $paymentTenderTypesArray[$code];
        }

        return $tenderTypeId;
    }
}
