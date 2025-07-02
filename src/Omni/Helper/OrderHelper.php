<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Client\Ecommerce\Entity\CustomerOrderCreateCODiscountLineV6;
use Ls\Omni\Client\Ecommerce\Entity\CustomerOrderCreateCOHeaderV6;
use Ls\Omni\Client\Ecommerce\Entity\CustomerOrderCreateCOLineV6;
use Ls\Omni\Client\Ecommerce\Entity\CustomerOrderCreateCOPaymentV6;
use Ls\Omni\Client\Ecommerce\Entity\CustomerOrderCreateV6;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\OrderCancelExResponse;
use Ls\Omni\Client\Ecommerce\Entity\RootCustomerOrderCreateV6;
use Ls\Omni\Client\Ecommerce\Entity\RootMobileTransaction;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetResponse;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetSalesByOrderIdResponse;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

/**
 * Useful helper functions for order
 *
 */
class OrderHelper extends AbstractHelperOmni
{
    /**
     * @var array
     */
    public $tendertypesArray = [];

    /**
     * @var mixed
     */
    public $currentOrder;

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
     *
     * Preparing order to sync with central
     *
     * @param Model\Order $order
     * @param RootMobileTransaction $oneListCalculateResponse
     * @return RootCustomerOrderCreateV6
     * @throws GuzzleException
     */
    public function prepareOrder(Model\Order $order, RootMobileTransaction $oneListCalculateResponse)
    {
        try {
            $rootCustomerOrderCreate = $this->createInstance(
                RootCustomerOrderCreateV6::class
            );

            $customerOrderCreateCoHeader = $this->createInstance(
                CustomerOrderCreateCOHeaderV6::class
            );

            $storeId = current((array)$oneListCalculateResponse->getMobiletransaction())->getStoreid();
            $cardId = current((array)$oneListCalculateResponse->getMobiletransaction())->getMembercardno();
            $customerEmail = $order->getCustomerEmail();
            $customerName = $order->getBillingAddress()->getFirstname() . ' ' .
                $order->getBillingAddress()->getLastname();

            if ($order->getShippingAddress()) {
                $shipToName = $order->getShippingAddress()->getFirstname() . ' ' .
                    $order->getShippingAddress()->getLastname();
            } else {
                $shipToName = $customerName;
            }

            $shippingMethod = $order->getShippingMethod(true);
            //TODO work on condition
            $isClickCollect = false;
            $carrierCode = '';
            $method = '';

            if ($shippingMethod !== null) {
                $carrierCode = $shippingMethod->getData('carrier_code');
                $method = $shippingMethod->getData('method');
                $isClickCollect = $carrierCode == 'clickandcollect';
            }

            $orderPayments = $this->setOrderPayments(
                $order,
                $cardId,
                $isClickCollect ? $order->getPickupStore() : $storeId
            );
            $rootCustomerOrderCreate->setCustomerordercreatecopaymentv6($orderPayments);

            //if the shipping address is empty, we use the contact address as shipping address.
            $customerOrderCreateCoHeader->addData(
                [
                    CustomerOrderCreateCOHeaderV6::MEMBER_CARD_NO => $cardId,
                    CustomerOrderCreateCOHeaderV6::SOURCE_TYPE => 1,
                    CustomerOrderCreateCOHeaderV6::CUSTOMER_NO => 44090,
                    CustomerOrderCreateCOHeaderV6::NAME => $customerName,
                    CustomerOrderCreateCOHeaderV6::ADDRESS => $order->getBillingAddress()->getStreetLine(1),
                    CustomerOrderCreateCOHeaderV6::ADDRESS2 => $order->getBillingAddress()->getStreetLine(2),
                    CustomerOrderCreateCOHeaderV6::CITY => $order->getBillingAddress()->getCity(),
                    CustomerOrderCreateCOHeaderV6::COUNTY => $order->getBillingAddress()->getRegion(),
                    CustomerOrderCreateCOHeaderV6::POST_CODE => $order->getBillingAddress()->getPostcode(),
                    CustomerOrderCreateCOHeaderV6::COUNTRY_REGION_CODE => $order->getBillingAddress()->getCountryId(),
                    CustomerOrderCreateCOHeaderV6::PHONE_NO => $order->getBillingAddress()->getTelephone(),
                    CustomerOrderCreateCOHeaderV6::EMAIL => $customerEmail,
                    CustomerOrderCreateCOHeaderV6::SHIP_TO_NAME => $shipToName,
                    CustomerOrderCreateCOHeaderV6::SHIP_TO_ADDRESS => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getStreetLine(1) :
                        $order->getBillingAddress()->getStreetLine(1),
                    CustomerOrderCreateCOHeaderV6::SHIP_TO_ADDRESS2 => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getStreetLine(2) :
                        $order->getBillingAddress()->getStreetLine(2),
                    CustomerOrderCreateCOHeaderV6::SHIP_TO_CITY => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getCity() :
                        $order->getBillingAddress()->getCity(),
                    CustomerOrderCreateCOHeaderV6::SHIP_TO_COUNTY => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getRegion() :
                        $order->getBillingAddress()->getRegion(),
                    CustomerOrderCreateCOHeaderV6::SHIP_TO_POST_CODE => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getPostcode() :
                        $order->getBillingAddress()->getPostcode(),
                    CustomerOrderCreateCOHeaderV6::SHIP_TO_COUNTRY_REGION_CODE => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getCountryId() :
                        $order->getBillingAddress()->getCountryId(),
                    CustomerOrderCreateCOHeaderV6::SHIP_TO_PHONE_NO => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getTelephone() :
                        $order->getBillingAddress()->getTelephone(),
                    CustomerOrderCreateCOHeaderV6::SHIP_TO_EMAIL => $customerEmail,
                    CustomerOrderCreateCOHeaderV6::EXTERNAL_ID => $order->getIncrementId(),
                    CustomerOrderCreateCOHeaderV6::CREATED_AT_STORE => $storeId,
                    CustomerOrderCreateCOHeaderV6::SHIP_ORDER => !$isClickCollect,
                ]
            );
            if (!$isClickCollect) {
                //TODO need to fix the length issue once LS Central allow more then 10 characters.
                $carrierCode = ($carrierCode) ? substr($carrierCode, 0, 10) : "";
                $method = ($method) ? substr($method, 0, 10) : "";
                $customerOrderCreateCoHeader->addData([
                    CustomerOrderCreateCOHeaderV6::SHIPPING_AGENT_CODE => $carrierCode,
                    CustomerOrderCreateCOHeaderV6::SHIPPING_AGENT_SERVICE_CODE => $method
                ]);
            }

            $pickupDateTimeslot = $order->getPickupDateTimeslot();
            if (!empty($pickupDateTimeslot)) {
                $dateTimeFormat = "Y-m-d\T" . "H:i:00";
                $pickupDateTime = $this->dateTime->date($dateTimeFormat, $pickupDateTimeslot);
                $customerOrderCreateCoHeader->addData([
                    CustomerOrderCreateCOHeaderV6::REQUESTED_DELIVERY_DATE => $pickupDateTime
                ]);
            }
            $rootCustomerOrderCreate->setCustomerordercreatecoheaderv6($customerOrderCreateCoHeader);
            $customerOrderCoLines = [];

            foreach ($oneListCalculateResponse->getMobiletransactionline() ?? [] as $id => $orderLine) {
                if ($orderLine->getLinetype() == 0) {
                    $customerOrderCoLine = $this->createInstance(
                        CustomerOrderCreateCOLineV6::class
                    );

                    $customerOrderCoLine->addData([
                        CustomerOrderCreateCOLineV6::LINE_NO => $orderLine->getLineno(),
                        CustomerOrderCreateCOLineV6::LINE_TYPE => $orderLine->getLinetype(),
                        CustomerOrderCreateCOLineV6::NUMBER => $orderLine->getNumber(),
                        CustomerOrderCreateCOLineV6::VARIANT_CODE => $orderLine->getVariantcode(),
                        CustomerOrderCreateCOLineV6::UNITOF_MEASURE_CODE => $orderLine->getUomid(),
                        CustomerOrderCreateCOLineV6::NET_PRICE => $orderLine->getNetprice(),
                        CustomerOrderCreateCOLineV6::PRICE => $orderLine->getPrice(),
                        CustomerOrderCreateCOLineV6::QUANTITY => $orderLine->getQuantity(),
                        CustomerOrderCreateCOLineV6::DISCOUNT_AMOUNT => $orderLine->getDiscountamount(),
                        CustomerOrderCreateCOLineV6::DISCOUNT_PERCENT => $orderLine->getDiscountpercent(),
                        CustomerOrderCreateCOLineV6::NET_AMOUNT => $orderLine->getNetamount(),
                        CustomerOrderCreateCOLineV6::VAT_AMOUNT => $orderLine->getTaxamount(),
                        CustomerOrderCreateCOLineV6::AMOUNT => $orderLine->getNetamount() + $orderLine->getTaxamount(),
                        CustomerOrderCreateCOLineV6::CLICK_AND_COLLECT => $isClickCollect,
                        CustomerOrderCreateCOLineV6::STORE_NO => $isClickCollect ? $order->getPickupStore() : $storeId,
                        CustomerOrderCreateCOLineV6::EXTERNAL_ID => $id,
                    ]);

                    $customerOrderCoLines[] = $customerOrderCoLine;
                }
            }

            $customerOrderDiscountCoLines = [];

            foreach ($oneListCalculateResponse->getMobiletransdiscountline() ?? [] as $id => $orderDiscountLine) {
                $customerOrderDiscountCoLine = $this->createInstance(
                    CustomerOrderCreateCODiscountLineV6::class
                );

                $customerOrderDiscountCoLine->addData([
                    CustomerOrderCreateCODiscountLineV6::LINE_NO => $orderDiscountLine->getLineno(),
                    CustomerOrderCreateCODiscountLineV6::ENTRY_NO => $orderDiscountLine->getNo(),
                    CustomerOrderCreateCODiscountLineV6::OFFER_NO => $orderDiscountLine->getOfferno(),
                    CustomerOrderCreateCODiscountLineV6::PERIODIC_DISC_TYPE =>
                        $orderDiscountLine->getPeriodicdisctype(),
                    CustomerOrderCreateCODiscountLineV6::PERIODIC_DISC_GROUP =>
                        $orderDiscountLine->getPeriodicdiscgroup(),
                    CustomerOrderCreateCODiscountLineV6::DESCRIPTION => $orderDiscountLine->getDescription(),
                    CustomerOrderCreateCODiscountLineV6::DISCOUNT_PERCENT => $orderDiscountLine->getDiscountpercent(),
                    CustomerOrderCreateCODiscountLineV6::DISCOUNT_AMOUNT => $orderDiscountLine->getDiscountamount(),
                ]);

                $customerOrderDiscountCoLines[] = $customerOrderDiscountCoLine;
            }
            //For click and collect we need to remove shipment charge orderline
            //For flat shipment it will set the correct shipment value into the order
            $customerOrderCoLines = $this->updateShippingAmount($customerOrderCoLines, $order, $storeId);
            // @codingStandardsIgnoreLine

            $rootCustomerOrderCreate
                ->setCustomerordercreatecolinev6($customerOrderCoLines)
                ->setCustomerordercreatecodiscountlinev6($customerOrderDiscountCoLines);
            return $rootCustomerOrderCreate;
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
     *
     * @param array $customerOrderCoLines
     * @param Order $order
     * @param string $storeCode
     * @return array
     */
    public function updateShippingAmount($customerOrderCoLines, Model\Order $order, string $storeCode)
    {
        $shipmentFeeId = $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID, $order->getStoreId());
        $shipmentTaxPercent = $this->getShipmentTaxPercent($order->getStore());
        $shippingAmount = $order->getShippingInclTax();

        if (isset($shipmentTaxPercent) && $shippingAmount > 0) {
            $netPriceFormula = 1 + $shipmentTaxPercent / 100;
            $netPrice = (float)$shippingAmount / $netPriceFormula;
            $taxAmount = (float)number_format(($shippingAmount - $netPrice), 2);
            // @codingStandardsIgnoreLine
            /*            $shipmentOrderLine = new Entity\OrderLine();
                        $shipmentOrderLine->setPrice($shippingAmount)
                            ->setAmount($shippingAmount)
                            ->setNetPrice($netPrice)
                            ->setNetAmount($netPrice)
                            ->setTaxAmount($taxAmount)
                            ->setItemId($shipmentFeeId)
                            ->setLineType(Entity\Enum\LineType::ITEM)
                            ->setQuantity(1)
                            ->setDiscountAmount($order->getShippingDiscountAmount());*/
            $orderLine = end($customerOrderCoLines);
            $lineNumber = $orderLine->getLineno();
            $lineNumber++;
            $customerOrderCoLine = $this->createInstance(
                CustomerOrderCreateCOLineV6::class
            );

            $customerOrderCoLine->addData([
                CustomerOrderCreateCOLineV6::LINE_NO => $lineNumber,
                CustomerOrderCreateCOLineV6::LINE_TYPE => 0,
                CustomerOrderCreateCOLineV6::NUMBER => $shipmentFeeId,
                CustomerOrderCreateCOLineV6::NET_PRICE => $netPrice,
                CustomerOrderCreateCOLineV6::PRICE => $shippingAmount,
                CustomerOrderCreateCOLineV6::QUANTITY => 1,
                CustomerOrderCreateCOLineV6::NET_AMOUNT => $netPrice,
                CustomerOrderCreateCOLineV6::VAT_AMOUNT => $taxAmount,
                CustomerOrderCreateCOLineV6::AMOUNT => $netPrice + $taxAmount,
                CustomerOrderCreateCOLineV6::STORE_NO => $storeCode
            ]);
            $customerOrderCoLines[] = $customerOrderCoLine;
        }

        return $customerOrderCoLines;
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
     *
     * Making Order Create request to Central
     *
     * @param RootCustomerOrderCreateV6 $request
     * @return Entity\OrderCreateResponse|ResponseInterface
     */
    public function placeOrder(RootCustomerOrderCreateV6 $request)
    {
        $operation = $this->createInstance(
            Operation\CustomerOrderCreateV6::class,
        );

        $operation->setOperationInput(
            [CustomerOrderCreateV6::CUSTOMER_ORDER_CREATE_V6_XML => $request]
        );

        return $operation->execute();
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
        $getParam = 'get' . $param;
        if (!property_exists($orderObj, $param)) {
            foreach ($orderObj as $order) {
                $value = $order->$getParam();
            }
        } else {
            $value = $orderObj->$getParam();
        }

        return $value;
    }

    /**
     * Set required payment methods for the order
     *
     * @param Order $order
     * @param $cardId
     * @param $storeId
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws GuzzleException
     */
    public function setOrderPayments(Model\Order $order, $cardId, $storeId)
    {
        $transId          = $order->getPayment()->getLastTransId();
        $ccType           = $order->getPayment()->getCcType() ? substr($order->getPayment()->getCcType(), 0, 10) : '';
        $cardNumber       = $order->getPayment()->getCcLast4();
        $paidAmount       = $order->getPayment()->getAmountPaid();
        $authorizedAmount = $order->getPayment()->getAmountAuthorized();
        $preApprovedDate  = date('Y-m-d', strtotime('+1 years'));

        $orderPaymentArray = [];
        //TODO change it to $paymentMethod->isOffline() == false when order edit option available for offline payments.
        $paymentCode  = $order->getPayment()->getMethodInstance()->getCode();
        $tenderTypeId = $this->getPaymentTenderTypeId($paymentCode);

        $noOrderPayment = $this->paymentLineNotRequiredPaymentMethods($order);

        $shippingMethod = $order->getShippingMethod(true);
        $isClickCollect = false;

        if ($shippingMethod !== null) {
            $carrierCode    = $shippingMethod->getData('carrier_code');
            $isClickCollect = $carrierCode == 'clickandcollect';
        }
        $lineNumber = 10000;
        if (!in_array($paymentCode, $noOrderPayment)) {
            // @codingStandardsIgnoreStart
            $orderPayment = $this->createInstance(CustomerOrderCreateCOPaymentV6::class);
            $orderPayment->addData(
                [
                    CustomerOrderCreateCOPaymentV6::STORE_NO => $isClickCollect ? $order->getPickupStore() : $storeId,
                    CustomerOrderCreateCOPaymentV6::LINE_NO  => $lineNumber,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_AMOUNT => $order->getGrandTotal(),
                    CustomerOrderCreateCOPaymentV6::TENDER_TYPE => $tenderTypeId,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_VALID_DATE => $preApprovedDate,
                    CustomerOrderCreateCOPaymentV6::EXTERNAL_REFERENCE => $order->getIncrementId(),
                    CustomerOrderCreateCOPaymentV6::CURRENCY_CODE => $order->getOrderCurrency()->getCurrencyCode(),
                    CustomerOrderCreateCOPaymentV6::CURRENCY_FACTOR => 1,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_AMOUNT_LCY => $order->getGrandTotal() * 1
                ]
            );
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
//            $orderPayment->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
//                ->setCurrencyFactor(1)
//                ->setLineNumber('1')
//                ->setExternalReference($order->getIncrementId())
//                ->setAmount($order->getGrandTotal());
            // For CreditCard/Debit Card payment  use Tender Type 1 for Cards
            if (!empty($transId)) {
                $orderPayment->addData(
                    [
                        CustomerOrderCreateCOPaymentV6::CARD_TYPE => $ccType,
                        CustomerOrderCreateCOPaymentV6::CARDOR_CUSTOMERNUMBER => $cardNumber,
                        CustomerOrderCreateCOPaymentV6::TOKEN_NO => $transId,
                    ]
                );
//                $orderPayment->setCardType($ccType);
//                $orderPayment->setCardNumber($cardNumber);
//                $orderPayment->setTokenNumber($transId);
                if (!empty($paidAmount)) {
                    $orderPayment->setType('1');
                } else {
                    if (!empty($authorizedAmount)) {
                        $orderPayment->setType('2');
                    } else {
                        $orderPayment->setType('0');
                    }
                }
            }

//            $orderPayment->setTenderType($tenderTypeId);
//            $orderPayment->setPreApprovedValidDate($preApprovedDate);
            $orderPaymentArray[] = $orderPayment;
            $lineNumber += 10000;
        }

        if ($order->getLsPointsSpent()) {
            $tenderTypeId = $this->getPaymentTenderTypeId(LSR::LS_LOYALTYPOINTS_TENDER_TYPE);
            $pointRate    = $this->loyaltyHelper->getPointRate();
//            // @codingStandardsIgnoreStart
//            $orderPaymentLoyalty = new Entity\OrderPayment();
//            // @codingStandardsIgnoreEnd
//            //default values for all payment types.
//            $orderPaymentLoyalty->setCurrencyCode('LOY')
//                ->setCurrencyFactor($pointRate)
//                ->setLineNumber('2')
//                ->setCardNumber($cardId)
//                ->setExternalReference($order->getIncrementId())
//                ->setAmount($order->getLsPointsSpent())
//                ->setPreApprovedValidDate($preApprovedDate)
//                ->setPaymentType(Entity\Enum\PaymentType::PAYMENT)
//                ->setTenderType($tenderTypeId);

            $orderPayment = $this->createInstance(CustomerOrderCreateCOPaymentV6::class);
            $orderPayment->addData(
                [
                    CustomerOrderCreateCOPaymentV6::STORE_NO => $isClickCollect ? $order->getPickupStore() : $storeId,
                    CustomerOrderCreateCOPaymentV6::LINE_NO  => $lineNumber,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_AMOUNT => $order->getLsPointsSpent(),
                    CustomerOrderCreateCOPaymentV6::TENDER_TYPE => $tenderTypeId,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_VALID_DATE => $preApprovedDate,
                    CustomerOrderCreateCOPaymentV6::EXTERNAL_REFERENCE => $order->getIncrementId(),
                    CustomerOrderCreateCOPaymentV6::CURRENCY_CODE => 'LOY',
                    CustomerOrderCreateCOPaymentV6::CURRENCY_FACTOR => $pointRate,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_AMOUNT_LCY => $order->getLsPointsSpent() * $pointRate,
                    CustomerOrderCreateCOPaymentV6::CARDOR_CUSTOMERNUMBER => $cardId,
                    CustomerOrderCreateCOPaymentV6::TYPE => '1',
                ]
            );
            $orderPaymentArray[] = $orderPayment;
            $lineNumber += 10000;
        }

        if ($order->getLsGiftCardAmountUsed()) {
            $tenderTypeId   = $this->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
//            $currencyFactor = 0;
//            if (version_compare(
//                $this->lsr->getCentralVersion($this->lsr->getCurrentWebsiteId(), ScopeInterface::SCOPE_WEBSITES),
//                '25',
//                '<'
//            )) {
//                $currencyFactor = 1;
//            }
            $giftCardCurrencyCode = $order->getOrderCurrency()->getCurrencyCode();
            // @codingStandardsIgnoreStart
//            $orderPaymentGiftCard = new Entity\OrderPayment();
//            // @codingStandardsIgnoreEnd
//            //default values for all payment typoes.
//            $orderPaymentGiftCard
//                ->setCurrencyFactor($currencyFactor)
//                ->setCurrencyCode($giftCardCurrencyCode)
//                ->setAmount($order->getLsGiftCardAmountUsed())
//                ->setLineNumber('3')
//                ->setCardNumber($order->getLsGiftCardNo())
//                ->setAuthorizationCode($order->getLsGiftCardPin())
//                ->setExternalReference($order->getIncrementId())
//                ->setPreApprovedValidDate($preApprovedDate)
//                ->setTenderType($tenderTypeId)
//                ->setPaymentType(Entity\Enum\PaymentType::PAYMENT);

            $orderPayment = $this->createInstance(CustomerOrderCreateCOPaymentV6::class);
            $orderPayment->addData(
                [
                    CustomerOrderCreateCOPaymentV6::STORE_NO => $isClickCollect ? $order->getPickupStore() : $storeId,
                    CustomerOrderCreateCOPaymentV6::LINE_NO  => $lineNumber,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_AMOUNT => $order->getLsGiftCardAmountUsed(),
                    CustomerOrderCreateCOPaymentV6::TENDER_TYPE => $tenderTypeId,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_VALID_DATE => $preApprovedDate,
                    CustomerOrderCreateCOPaymentV6::EXTERNAL_REFERENCE => $order->getIncrementId(),
                    CustomerOrderCreateCOPaymentV6::CURRENCY_CODE => $giftCardCurrencyCode,
                    CustomerOrderCreateCOPaymentV6::CURRENCY_FACTOR => 0,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_AMOUNT_LCY => 0,
                    CustomerOrderCreateCOPaymentV6::AUTHORIZATION_CODE => $order->getLsGiftCardPin(),
                    CustomerOrderCreateCOPaymentV6::CARDOR_CUSTOMERNUMBER => $order->getLsGiftCardNo(),
                    CustomerOrderCreateCOPaymentV6::TYPE => '1',
                ]
            );
            $orderPaymentArray[] = $orderPayment;
        }

        return $orderPaymentArray;
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
        $orderCardId = $order->getCardId();
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
            $type == DocumentIdType::RECEIPT
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
            $dateTime = $this->timezone->date($date)->format($format);

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
                    $currency  = $order->getStoreCurrency();
                    $orderType = $order->getIdType();
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
}
