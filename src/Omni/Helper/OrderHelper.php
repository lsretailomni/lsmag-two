<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\SalesEntryStatus;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ShippingStatus;
use \Ls\Omni\Client\Ecommerce\Entity\OrderCancelExResponse;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetResponse;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetSalesByOrderIdResponse;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;

use \Ls\Omni\Client\Ecommerce\Operation\GetMemContSalesHist_GetMemContSalesHist;
use \Ls\Omni\Client\Ecommerce\Operation\GetSelectedSalesDoc_GetSelectedSalesDoc;

use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CurrencyFactory;

/**
 * Useful helper functions for order
 *
 */
class OrderHelper extends AbstractHelper
{
    /**
     * @var mixed
    */
    public $currentOrder;
    
    /**
     * @param Context $context
     * @param Model\Order $order
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param GiftCardHelper $giftCardHelper
     * @param OrderRepository $orderRepository
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param LSR $lsr
     * @param Order $orderResourceModel
     * @param Json $json
     * @param Registry $registry
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezoneInterface
     * @param StoreManagerInterface $storeManager
     * @param StoreHelper $storeHelper
     * @param CurrencyFactory $currencyFactory
     * @param ConfigInterface $config
     */
    public function __construct(
        public Context $context,
        public Model\Order $order,
        public BasketHelper $basketHelper,
        public LoyaltyHelper $loyaltyHelper,
        public GiftCardHelper $giftCardHelper,
        public Model\OrderRepository $orderRepository,
        public CustomerSession $customerSession,
        public CheckoutSession $checkoutSession,
        public LSR $lsr,
        public Order $orderResourceModel,
        public Json $json,
        public Registry $registry,
        public DateTime $dateTime,
        public TimezoneInterface $timezoneInterface,
        public StoreManagerInterface $storeManager,
        public StoreHelper $storeHelper,
        public CurrencyFactory $currencyFactory,
        public ConfigInterface $config
    ) {
        parent::__construct($context);        
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

            if (version_compare($this->lsr->getOmniVersion(), '2023.08.1', '>=')) {
                $oneListCalculateResponse->setCurrencyFactor($this->loyaltyHelper->getPointRate($order->getStoreId()));
                $oneListCalculateResponse->setCurrency($order->getOrderCurrencyCode());
            }

            if ($isClickCollect) {
                $oneListCalculateResponse->setOrderType(Entity\Enum\OrderType::CLICK_AND_COLLECT);
            } else {
                $oneListCalculateResponse->setOrderType(Entity\Enum\OrderType::SALE);
                //TODO need to fix the length issue once LS Central allow more then 10 characters.
                $carrierCode = ($carrierCode) ? substr($carrierCode, 0, 10) : "";
                $oneListCalculateResponse->setShippingAgentCode($carrierCode);
                $method = ($method) ? substr($method, 0, 10) : "";
                $oneListCalculateResponse->setShippingAgentServiceCode($method);
            }
            $pickupDateTimeslot = $order->getPickupDateTimeslot();
            if (!empty($pickupDateTimeslot)) {
                $dateTimeFormat = "Y-m-d\T" . "H:i:00";
                $pickupDateTime = $this->dateTime->date($dateTimeFormat, $pickupDateTimeslot);
                $oneListCalculateResponse->setRequestedDeliveryDate($pickupDateTime);
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

            if (version_compare($this->lsr->getOmniVersion($order->getStoreId()), '2023.05.1', '>=')) {
                $request->setReturnOrderIdOnly(true);
            }

            $oneListCalculateResponse->setOrderLines($orderLinesArray);
            $request->setRequest($oneListCalculateResponse);
            return $request;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param Model\Order $order
     * @param $oneListCalculateResponse
     * @return Entity\OrderEdit
     */
    public function prepareOrderEdit(Model\Order $order, $oneListCalculateResponse)
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
                $carrierCode = ($carrierCode) ? substr($carrierCode, 0, 10) : "";
                $oneListCalculateResponse->setShippingAgentCode($carrierCode);
                $method = ($method) ? substr($method, 0, 10) : "";
                $oneListCalculateResponse->setShippingAgentServiceCode($method);
            }
            $pickupDateTimeslot = $order->getPickupDateTimeslot();
            if (!empty($pickupDateTimeslot)) {
                $dateTimeFormat = "Y-m-d\T" . "H:i:00";
                $pickupDateTime = $this->dateTime->date($dateTimeFormat, $pickupDateTimeslot);
                $oneListCalculateResponse->setRequestedDeliveryDate($pickupDateTime);
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

            if (version_compare($this->lsr->getOmniVersion(), '2023.05.1', '>=')) {
                $request->setReturnOrderIdOnly(true);
            }

            $oneListCalculateResponse->setOrderLines($orderLinesArray);
            $request->setRequest($oneListCalculateResponse);
            return $request;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * Get shipment tax percent
     *
     * @param $storeId
     * @return string
     */
    public function getShipmentTaxPercent($storeId)
    {
        $shipmentTaxPercent = $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_TAX, $storeId);

        return !empty($shipmentTaxPercent) &&
        str_contains($shipmentTaxPercent, '#') ?
            substr($shipmentTaxPercent, strrpos($shipmentTaxPercent, '#') + 1) : $shipmentTaxPercent;
    }

    /**
     * Update shipping amount to shipment order line
     * @param $orderLines
     * @param $order
     * @return mixed
     * @throws InvalidEnumException
     */
    public function updateShippingAmount($orderLines, $order)
    {
        $shipmentFeeId      = $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID, $order->getStoreId());
        $shipmentTaxPercent = $this->getShipmentTaxPercent($order->getStore());
        $shippingAmount     = $order->getShippingInclTax();

        if (isset($shipmentTaxPercent) && $shippingAmount > 0) {
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
     * Set shipment line properties
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
        $region = $magentoAddress->getRegion() ? substr($magentoAddress->getRegion(), 0, 30) : null;
        $omniAddress
            ->setCity($magentoAddress->getCity())
            ->setCountry($magentoAddress->getCountryId())
            ->setCounty($region)
            ->setPostCode($magentoAddress->getPostcode())
            ->setPhoneNumber($magentoAddress->getTelephone());

        return $omniAddress;
    }

    /**
     * Fetch node values based on the parameter passed
     * @param $orderObj
     * @param $param
     * @return mixed
     */
    public function getParameterValues($orderObj, $param)
    {
        $value    = null;
        if(array_key_exists($param, $orderObj->getData())) {
            $value = $orderObj->getData($param);
        }
        return $value;
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
        $ccType           = $order->getPayment()->getCcType() ? substr($order->getPayment()->getCcType(), 0, 10) : '';
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

        $noOrderPayment = $this->paymentLineNotRequiredPaymentMethods($order);

        if (!in_array($paymentCode, $noOrderPayment)) {
            // @codingStandardsIgnoreStart
            $orderPayment = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
            $orderPayment->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
                ->setCurrencyFactor(1)
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
            //default values for all payment types.
            $orderPaymentLoyalty->setCurrencyCode('LOY')
                ->setCurrencyFactor($pointRate)
                ->setLineNumber('2')
                ->setCardNumber($cardId)
                ->setExternalReference($order->getIncrementId())
                ->setAmount($order->getLsPointsSpent())
                ->setPreApprovedValidDate($preApprovedDate)
                ->setPaymentType(Entity\Enum\PaymentType::PAYMENT)
                ->setTenderType($tenderTypeId);
            $orderPaymentArray[] = $orderPaymentLoyalty;
        }

        if ($order->getLsGiftCardAmountUsed()) {
            $tenderTypeId   = $this->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
            $currencyFactor = 0;
            if (version_compare(
                $this->lsr->getCentralVersion($this->lsr->getCurrentWebsiteId(), ScopeInterface::SCOPE_WEBSITES),
                '25',
                '<'
            )) {
                $currencyFactor = 1;
            }
            $giftCardCurrencyCode = $order->getOrderCurrency()->getCurrencyCode();
            // @codingStandardsIgnoreStart
            $orderPaymentGiftCard = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
            $orderPaymentGiftCard
                ->setCurrencyFactor($currencyFactor)
                ->setCurrencyCode($giftCardCurrencyCode)
                ->setAmount($order->getLsGiftCardAmountUsed())
                ->setLineNumber('3')
                ->setCardNumber($order->getLsGiftCardNo())
                ->setAuthorizationCode($order->getLsGiftCardPin())
                ->setExternalReference($order->getIncrementId())
                ->setPreApprovedValidDate($preApprovedDate)
                ->setTenderType($tenderTypeId)
                ->setPaymentType(Entity\Enum\PaymentType::PAYMENT);
            $orderPaymentArray[] = $orderPaymentGiftCard;
        }

        return $orderPaymentArrayObject->setOrderPayment($orderPaymentArray);
    }

    /**
     * This function is overriding in hospitality module
     *
     * Payment methods with no need to send in payment line
     *
     * @param Model\Order $order
     * @return string[]
     */
    public function paymentLineNotRequiredPaymentMethods(Model\Order $order)
    {
        return ['ls_payment_method_pay_at_store', 'free'];
    }

    /**
     * Get Current Customer Order History
     * 
     * @param $maxNumberOfEntries
     * @return array|Entity\GetMemContSalesHist_GetMemContSalesHistResponse|null
     */
    public function getCurrentCustomerOrderHistory($maxNumberOfEntries = null)
    {
        $response = null;
        $cardId   = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        if ($cardId == null) {
            return $response;
        }
        // @codingStandardsIgnoreStart
        $getSalesHistory = new GetMemContSalesHist_GetMemContSalesHist();
        $getSalesHistory->setOperationInput(
            [
                'memberCardNo' => $cardId,
                'storeNo' => "",
                'dateFilter' => "1990-01-01",
                'dateGreaterThan'=> true,
                'maxResultContacts'=> 0
            ]
        );

        try {
            $response = $getSalesHistory->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getRecords()[0]->getLSCMemberSalesBuffer() : $response;
    }

    /**
     * Validate order have return sale or not
     *
     * @return mixed
     */
    public function hasReturnSale($orderTransactions)
    {
        $hasReturnSale = false;

        foreach ($orderTransactions as $transaction) {
            if ($hasReturnSale = $this->getParameterValues($transaction, "HasReturnSale")) {
                break;
            }
        }

        return $hasReturnSale;
    }

    /**
     * This function is overriding in hospitality module
     * @param $docId
     * @param string $type
     * @return Entity\SalesEntry|Entity\SalesEntryGetResponse|ResponseInterface|null
     * @throws InvalidEnumException
     */
    public function getOrderDetailsAgainstId($docId, $type)
    {
        $response = null;
        $typeId   = $this->getOrderTypeId($type);
        // @codingStandardsIgnoreStart
        $request = new GetSelectedSalesDoc_GetSelectedSalesDoc();
        $request->setOperationInput(
            [
                'documentSourceType' => $typeId,
                'documentID' => $docId
            ]
        );
        
        // @codingStandardsIgnoreEnd
        try {
            $response = $request->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getRecords()[0]->getData() : $response;
    }

    /**
     * Get sales order by order id
     *
     * @param $docId
     * @return SalesEntry[]|Entity\SalesEntryGetSalesByOrderIdResponse|ResponseInterface|null
     */
    public function getSalesOrderByOrderId($docId)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\SalesEntryGetSalesByOrderId();
        $order   = new Entity\SalesEntryGetSalesByOrderId();
        $order->setOrderId($docId);
        // @codingStandardsIgnoreEnd
        try {
            $response = $request->execute($order);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response && $response->getSalesEntryGetSalesByOrderIdResult() ?
            $response->getSalesEntryGetSalesByOrderIdResult()->getSalesEntry() : $response;
    }

    /**
     * Get sales order by order id
     *
     * @param $docId
     * @param $type
     * @return SalesEntry[]|Entity\SalesEntryGetSalesExtByOrderIdResponse|ResponseInterface
     * @throws InvalidEnumException
     */
    public function getSalesOrderByOrderIdNew($docId, $type)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\SalesEntryGetSalesExtByOrderId();
        $order   = new Entity\SalesEntryGetSalesExtByOrderId();
        $order->setOrderId($docId);
        // @codingStandardsIgnoreEnd
        try {
            $response = $request->execute($order);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if ($response && $response->getSalesEntryGetSalesExtByOrderIdResult()) {
            if (!empty($response->getSalesEntryGetSalesExtByOrderIdResult()->getSalesEntries()->getSalesEntry())) {
                return $response->getSalesEntryGetSalesExtByOrderIdResult()->getSalesEntries()->getSalesEntry();
            } elseif (!empty($response->getSalesEntryGetSalesExtByOrderIdResult()->getShipments()
                ->getSalesEntryShipment())) {
                $result          = $response->getSalesEntryGetSalesExtByOrderIdResult();
                $cardId          = $result->getCardId();
                $orderId         = $result->getOrderId();
                $response        = $result->getShipments()->getSalesEntryShipment();
                $salesEntryArray = [];
                foreach ($response as $shipment) {
                    $salesEntry          = new SalesEntry();
                    $salesEntryLineArray = new Entity\ArrayOfSalesEntryLine();
                    $salesEntryLines     = [];
                    $salesEntry->setId($shipment->getId());
                    $salesEntry->setIdType($type);
                    $salesEntry->setShipToAddress($shipment->getAddress());
                    $salesEntry->setCustomerOrderNo($orderId);
                    $salesEntry->setDocumentRegTime($shipment->getShipmentDate());
                    $salesEntry->setStatus(Entity\Enum\SalesEntryStatus::PROCESSING);
                    $salesEntry->setId($shipment->getId());
                    $salesEntry->setCardId($cardId);
                    $salesEntry->setShippingAgentCode($shipment->getAgentCode());
                    $salesEntry->setContactName($shipment->getName());
                    foreach ($shipment->getLines() as $line) {
                        $salesEntryLine = new Entity\SalesEntryLine();
                        $salesEntryLine->setItemId($line->getItemId());
                        $salesEntryLine->setLineNumber($line->getLineNumber());
                        $salesEntryLine->setItemDescription($line->getItemDescription());
                        $salesEntryLine->setUomId($line->getUomId());
                        $salesEntryLine->setVariantId($line->getVariantId());
                        $salesEntryLine->setQuantity($line->getQuantity());
                        $salesEntryLines[] = $salesEntryLine;
                    }
                    $salesEntryLineArray->setSalesEntryLine($salesEntryLines);
                    $salesEntry->setLines($salesEntryLineArray);
                    $salesEntryArray[] = $salesEntry;
                }
                return $salesEntryArray;
            } else {
                return null;
            }
        }

        return null;
    }

    /**
     * Get sales return details
     *
     * @param $docId
     * @return SalesEntry[]|Entity\SalesEntryGetReturnSalesResponse|ResponseInterface|null
     */
    public function getReturnDetailsAgainstId(
        $docId
    ) {
        $response = null;
        // @codingStandardsIgnoreStart
        $returnRequest = new Operation\SalesEntryGetReturnSales();
        $returnOrder   = new Entity\SalesEntryGetReturnSales();
        $returnOrder->setReceiptNo($docId);
        // @codingStandardsIgnoreEnd
        try {
            $response = $returnRequest->execute($returnOrder);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response && $response->getSalesEntryGetReturnSalesResult() ?
            $response->getSalesEntryGetReturnSalesResult()->getSalesEntry() : $response;
    }

    /**
     * Validate if the order using CardId
     * @param $order
     * @return bool
     */
    public function isAuthorizedForOrder(
        $order
    ) {
        $cardId      = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        $order       = $this->getOrder();
        $orderCardId = $order->getData('Member Card No.');
        
        if ($cardId == $orderCardId) {
            return true;
        }
        return false;
    }

    /**
     * Validate the order using CardId
     * @param $order
     * @return bool
     */
    public function isAuthorizedForReturnOrder($order): bool
    {
        $orderCardId = null;
        $cardId      = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        foreach ($order as $ordItem) {
            $orderCardId = $ordItem->getCardId();
            break;
        }

        if ($cardId == $orderCardId) {
            return true;
        }

        return false;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Fetch Order
     *
     * @param $docId
     * @param $type
     * @return SalesEntry|SalesEntry[]|SalesEntryGetResponse|SalesEntryGetSalesByOrderIdResponse|ResponseInterface|null
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function fetchOrder($docId, $type)
    {
        if (version_compare($this->lsr->getOmniVersion(), '2022.5.1', '>=') &&
            $type == 0
        ) {
            if (version_compare($this->lsr->getOmniVersion(), '2023.10', '>')
                && $this->lsr->getWebsiteConfig(LSR::SC_REPLICATION_CENTRAL_TYPE, $this->lsr->getCurrentWebsiteId())
                == LSR::OnPremise) {
                $response = $this->getSalesOrderByOrderIdNew($docId, $type);
            } else {
                $response = $this->getSalesOrderByOrderId($docId);
            }
            if (empty($response)) {
                $response = $this->getOrderDetailsAgainstId($docId, $type);
            }
        } else {
            $response = $this->getOrderDetailsAgainstId($docId, $type);
        }

        return $response;
    }

    /**
     * Set LS Central order details in registry.
     * @param $order
     */
    public function setOrderInRegistry($order)
    {
        if (!$this->getGivenValueFromRegistry('current_order')) {
            $this->registerGivenValueInRegistry('current_order', $order);
        }
    }

    /**
     * Get respective magento order given Central sales entry Object
     *
     * @param $salesEntry
     */
    public function setCurrentMagOrderInRegistry($salesEntry)
    {
        $order = $this->getOrderByDocumentId($salesEntry);
        $this->registerGivenValueInRegistry('current_mag_order', $order);
    }

    /**
     * Register given value in registry
     *
     * @param $key
     * @param $value
     * @return void
     */
    public function registerGivenValueInRegistry($key, $value)
    {
        if ($this->registry->registry($key)) {
            $this->registry->unregister($key);
        }

        $this->registry->register($key, $value);
    }

    /**
     * Get given value from registry
     *
     * @param $key
     * @return mixed|null
     */
    public function getGivenValueFromRegistry($key)
    {
        return $this->registry->registry($key);
    }

    /**
     * Retrieve current order model instance
     *
     * @param $all
     * @return false|mixed|null
     */
    public function getOrder($all = false)
    {
        if ($all) {
            return $this->registry->registry('current_order');
        }
        return is_array($this->registry->registry('current_order')) ?
            current($this->registry->registry('current_order')) : $this->registry->registry('current_order');
    }

    /**
     * Get respective magento order given commerce service sales entry
     *
     * @param $salesEntry
     * @return array|OrderInterface
     */
    public function getOrderByDocumentId($response)
    {
        $order = [];
        try {
            $documentId = $response->getData('Document ID');

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
     * Get magento order given document_id
     *
     * @param string $documentId
     * @return false|mixed|null
     */
    public function getMagentoOrderGivenDocumentId($documentId)
    {
        $order     = null;
        $orderList = $this->orderRepository->getList(
            $this->basketHelper->getSearchCriteriaBuilder()->
            addFilter('document_id', $documentId)->create()
        )->getItems();

        if (!empty($orderList)) {
            $order = reset($orderList);
        }

        return $order;
    }

    /**
     * Get magento order given entity_id
     *
     * @param $entityId
     * @return OrderInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getMagentoOrderGivenEntityId($entityId)
    {
        return $this->orderRepository->get($entityId);
    }

    /**
     * Return orders from Magento which are yet to be sent to Central and are not payment_review and canceled
     *
     * @param $storeId
     * @param $pageSize
     * @param $filterOptions
     * @param $customerId
     * @param $sortOrder
     * @param $isOrderEdit
     * @return OrderInterface[]|null
     * @throws NoSuchEntityException
     */
    public function getOrders(
        $storeId = null,
        $pageSize = -1,
        $filterOptions = true,
        $customerId = 0,
        $sortOrder = null,
        $isOrderEdit = false
    ) {
        $orders    = null;
        $store     = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        try {
            $orderStatuses   = $this->lsr->getWebsiteConfig(
                LSR::LSR_RESTRICTED_ORDER_STATUSES,
                $websiteId
            );
            $criteriaBuilder = $this->basketHelper->getSearchCriteriaBuilder();

            if ($filterOptions) {
                if (!empty($orderStatuses)) {
                    $criteriaBuilder->addFilter('status', explode(',', $orderStatuses), 'nin');
                }

                $criteriaBuilder->addFilter('document_id', null, 'null');
            }

            if ($customerId) {
                $criteriaBuilder->addFilter('customer_id', $customerId, 'eq');
            }

            if ($isOrderEdit) {
                $criteriaBuilder = $criteriaBuilder->addFilter('edit_increment', null, 'neq');
                $criteriaBuilder = $criteriaBuilder->addFilter('ls_order_edit', false, 'eq');
            }

            if ($sortOrder) {
                $criteriaBuilder = $criteriaBuilder->setSortOrders([$sortOrder]);
            }

            if ($pageSize != -1) {
                $criteriaBuilder->setPageSize($pageSize);
            }

            $searchCriteria = $criteriaBuilder->create();
            $orders         = $this->orderRepository->getList($searchCriteria)->getItems();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $orders;
    }

    /**
     * Get active web store details
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getActiveWebStore()
    {
        return $this->lsr->getActiveWebStore();
    }

    /**
     * Error handler
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
     * Setting Adyen payment gateway parameters
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
            try {
                $this->orderRepository->save($order);
                $order = $this->orderRepository->get($order->getEntityId());
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }
        return $order;
    }

    /**
     * This function is overriding in hospitality module
     *
     * For cancelling the order in LS central
     * @param $documentId
     * @param $storeId
     * @return OrderCancelExResponse|ResponseInterface|string|null
     */
    public function orderCancel($documentId, $storeId)
    {
        $response = null;
        $request  = new Entity\OrderCancelEx();
        $request->setOrderId($documentId);
        $request->setStoreId($storeId);
        $request->setUserId("");
        $operation = new Operation\OrderCancelEx();
        try {
            $response = $operation->execute($request);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response ? $response->getOrderCancelExResult() : $response;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Formulate order cancel response
     *
     * @param $response
     * @param $order
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function formulateOrderCancelResponse($response, $order)
    {
        if (version_compare($this->lsr->getOmniVersion(), '2022.12.0', '>')) {
            if (!$response) {
                $this->formulateException($order);
            }
        } else {
            if ($response !== "") {
                $this->formulateException($order);
            }
        }
    }

    /**
     * Formulate Exception in case of error
     *
     * @param $order
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function formulateException($order)
    {
        $message = __('Order could not be canceled from LS Central. Try again later.');
        $order->addCommentToStatusHistory($message);
        $this->orderRepository->save($order);
        throw new LocalizedException(__($message));
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
            $customerOrderNo = $this->getParameterValues($salesEntry, "CustomerOrderNo");
            return $customerOrderNo;
        }

        return $this->getParameterValues($salesEntry, "Id");
    }

    /**
     * Get configuration value for tender type
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getPaymentTenderMapping()
    {
        if ($this->tendertypesArray) {
            return $this->tendertypesArray;
        }
        $paymentTenderTypesArray = $this->lsr->getStoreConfig(
            LSR::LSR_PAYMENT_TENDER_TYPE_MAPPING,
            $this->basketHelper->getCorrectStoreIdFromCheckoutSession() ?? $this->lsr->getCurrentStoreId()
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
     * @throws NoSuchEntityException
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

    /**
     * Return date time
     *
     * @return DateTime
     */
    public function getDateTimeObject()
    {
        return $this->dateTime;
    }

    /**
     * Get formatted order date in local timezone
     *
     * @param $date
     * @return string
     */
    public function getFormattedDate($date)
    {
        try {
            $format   = 'd/m/y h:i:s A';
            $dateTime = $this->timezoneInterface->date($date)->format($format);

            return $dateTime;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $date;
    }

    /**
     * Order status is not one of restricted order statuses
     *
     * @param Model\Order $order
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isAllowed($order)
    {
        $websiteId     = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
        $orderStatuses = $this->lsr->getWebsiteConfig(
            LSR::LSR_RESTRICTED_ORDER_STATUSES,
            $websiteId
        );

        $status = $order->getStatus();

        $check = empty($orderStatuses) || !(in_array($status, explode(',', $orderStatuses)));
        return $check;
    }

    /**
     * Getting price with currency from store
     *
     * @param $priceCurrency
     * @param $amount
     * @param $currency
     * @param $storeId
     * @param null $orderType
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getPriceWithCurrency(
        $priceCurrency,
        $amount,
        $currency,
        $storeId,
        $orderType = null
    ) {
        $magentoOrder = $this->getGivenValueFromRegistry('current_mag_order');
        $currencyObject = null;
        $currentStoreCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();

        if ($magentoOrder) {
            if ($magentoOrder->getOrderCurrencyCode() !== $currentStoreCurrencyCode) {
                $amount = $this->basketHelper->getItemHelper()->convertToCurrentStoreCurrency(
                    $amount,
                    $currentStoreCurrencyCode,
                    $magentoOrder->getOrderCurrencyCode()
                );
            }
            $currency = $currentStoreCurrencyCode;
        }

        if (empty($currency) && empty($storeId) && !$this->currentOrder) {
            $this->currentOrder = $this->getGivenValueFromRegistry('current_order');
        }

        if (empty($currency) && empty($storeId) && empty($orderType) && $this->currentOrder) {
            if (is_array($this->currentOrder)) {
                foreach ($this->currentOrder as $order) {
                    if(!is_array($order)) {
                        $currency  = $order->getStoreCurrencyCode();
                        $orderType = $order->getDocumentSourceType();
                        $orderType = $this->getOrderType($orderType);
                    }
                    
                }
            } else {
                $currency  = $this->currentOrder->getStoreCurrency();
                $orderType = $this->currentOrder->getIdType();
            }
        }

        if ($orderType != DocumentIdType::RECEIPT) {
            $currency = null;
        }

        if (!empty($currency)) {
            $allowedCurrencies = $this->config->getAllowedCurrencies();

            if (in_array($currency, $allowedCurrencies)) {
                $currencyObject = $this->currencyFactory->create()->load($currency);
            }
        }

        return $priceCurrency->format($amount, false, 2, null, $currencyObject);
    }

    /**
     * Get order type based on the provided ID type
     *
     * @param int $idType The type of ID to determine the order status
     * @return string The corresponding order status based on the ID type
     */
    public function getOrderType($idType)
    {
        switch ($idType)
        {
            case 1:
                return DocumentIdType::ORDER;
            case 2:
                return DocumentIdType::HOSP_ORDER;
        }
        return DocumentIdType::RECEIPT;
    }

    /**
     * Get order type based on the provided ID type
     *
     * @param int $idType The type of ID to determine the order status
     * @return string The corresponding order status based on the ID type
     */
    public function getOrderTypeId($type)
    {
        switch ($type)
        {
            case DocumentIdType::ORDER:
                return 1;
            case DocumentIdType::HOSP_ORDER:
                return 2;
            case DocumentIdType::RECEIPT:
                return 0;
        }
        return 0;
    }

    /**
     * Processes order data and updates fields based on order types and conditions.
     *
     * @param array $orders
     * @return array
     */
    public function processOrderData($orders)
    {
        foreach ($orders as $order) {
            $order['IdType']          = $this->getOrderType($order['Document Source Type']);
            $order['CustomerOrderNo'] = ($order['Customer Document ID']) ? $order['Customer Document ID'] : $order['Document ID'];

            switch ($order['IdType']) {
                case 0: //Receipt
                    $order['Status']               = SalesEntryStatus::COMPLETE;
                    $order['ShippingStatus']       = ShippingStatus::SHIPPED;
                    $order['ClickAndCollectOrder'] = (is_null($order['Customer Document ID']) || $order['Customer Document ID'] === '') == false;
                    if((is_null($order['Ship-to Name']) || $order['Ship-to Name'] === '')) {
                        $order['Ship-to Name']  = $order['Name'];
                        $order['Ship-to Email'] = $order['Email'];
                    }
                    break;
                case 1: //Order
                    $order['Status']               = $order['Sale Is Return Sale'] ? SalesEntryStatus::CANCELED : SalesEntryStatus::CREATED;
                    $order['ShippingStatus']       = ShippingStatus::NOT_YET_SHIPPED;
                    $order['CreateAtStoreId']      = $order['Store No.'];
                    $order['ClickAndCollectOrder'] = "Need to implement";
                    break;
                case 2: //HOSP ORDER
                    $order['CreateTime']           = $order['Date Time'];
                    $order['CreateAtStoreId']      = $order['Store No.'];
                    $order['Status']               = SalesEntryStatus::PROCESSING;
                    $order['ShippingStatus']       = ShippingStatus::SHIPPIG_NOT_REQUIRED;
                    if((is_null($order['Ship-to Name']) || $order['Ship-to Name'] === '')) {
                        $order['Ship-to Name']  = $order['Name'];
                        $order['Ship-to Email'] = $order['Email'];
                    }
                    break;
            }
        }
        return $orders;
    }
}
