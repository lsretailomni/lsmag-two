<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\LineType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\PaymentType;
use \Ls\Omni\Client\CentralEcommerce\Entity;
use \Ls\Omni\Client\CentralEcommerce\Entity\CustomerOrderCreateCODiscountLineV6;
use \Ls\Omni\Client\CentralEcommerce\Entity\CustomerOrderCreateCOHeaderV6;
use \Ls\Omni\Client\CentralEcommerce\Entity\CustomerOrderCreateCOLineV6;
use \Ls\Omni\Client\CentralEcommerce\Entity\CustomerOrderCreateCOPaymentV6;
use \Ls\Omni\Client\CentralEcommerce\Entity\CustomerOrderCreateV6;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\SalesEntryStatus;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ShippingStatus;
use \Ls\Omni\Client\Ecommerce\Entity\OrderCancelExResponse;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootCustomerOrderCreateV6;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootMobileTransaction;
use \Ls\Omni\Client\CentralEcommerce\Operation;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetSalesInfoByOrderId_GetSalesInfoByOrderId;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetSalesReturnById_GetSalesReturnById;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetMemContSalesHist_GetMemContSalesHist;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetSelectedSalesDoc_GetSelectedSalesDoc;
use \Ls\Omni\Client\CentralEcommerce\Entity\CustomerOrderCancel as CustomerOrderCancelRequest;
use \Ls\Omni\Client\CentralEcommerce\Operation\CustomerOrderCancel;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model;
use Magento\Sales\Model\Order;

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
     * Place order by Id
     *
     * @param $orderId
     * @param \Ls\Omni\Client\Ecommerce\Entity\Order $oneListCalculateResponse
     */
    public function placeOrderById($orderId, \Ls\Omni\Client\Ecommerce\Entity\Order $oneListCalculateResponse)
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
        $rootCustomerOrderCreate = $this->createInstance(
            RootCustomerOrderCreateV6::class
        );

        try {
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
                    CustomerOrderCreateCOHeaderV6::CURRENCY_FACTOR =>
                        $this->loyaltyHelper->getPointRate($order->getStoreId()),
                    CustomerOrderCreateCOHeaderV6::CURRENCY_CODE => $order->getOrderCurrencyCode(),
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
                $dateTimeFormat = "Y-m-d";
                $pickupDateTime = $this->dateTime->date($dateTimeFormat, $pickupDateTimeslot);
                $customerOrderCreateCoHeader->addData([
                    CustomerOrderCreateCOHeaderV6::REQUESTED_DELIVERY_DATE => $pickupDateTime
                ]);
            }
            $rootCustomerOrderCreate->setCustomerordercreatecoheaderv6($customerOrderCreateCoHeader);
            $customerOrderCoLines = [];

            foreach ($oneListCalculateResponse->getMobiletransactionline() ?? [] as $id => $orderLine) {
                if ($orderLine->getLinetype() == 0) {

                    $serviceItem = $this->itemHelper->checkAndUpdateServiceItems($orderLine);

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
                        CustomerOrderCreateCOLineV6::SERVICE_ITEM => $serviceItem,
                        CustomerOrderCreateCOLineV6::DISCOUNT_AMOUNT => $orderLine->getDiscountamount(),
                        CustomerOrderCreateCOLineV6::DISCOUNT_PERCENT => $orderLine->getDiscountpercent(),
                        CustomerOrderCreateCOLineV6::NET_AMOUNT => $orderLine->getNetamount(),
                        CustomerOrderCreateCOLineV6::VAT_AMOUNT => $orderLine->getTaxamount(),
                        CustomerOrderCreateCOLineV6::AMOUNT =>
                            ($orderLine->getPrice() * $orderLine->getQuantity()) - $orderLine->getDiscountamount(),
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
                    CustomerOrderCreateCODiscountLineV6::DISCOUNT_TYPE => $orderDiscountLine->getDiscounttype(),
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

        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $rootCustomerOrderCreate;
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
            $orderLine = end($customerOrderCoLines);
            $lineNumber = $orderLine->getLineno();
            $lineNumber = $lineNumber + 10000;
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
                CustomerOrderCreateCOLineV6::SERVICE_ITEM => true,
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
     *
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
     * @return \Ls\Omni\Client\Ecommerce\Entity\OrderCreateResponse|ResponseInterface
     */
    public function placeOrder($request)
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
     * This function is overriding in hospitality module
     *
     * Extract document_id from order response
     *
     * @param $response
     * @return string
     */
    public function getDocumentIdFromResponseBasedOnIndustry($response)
    {
        return $response->getCustomerorderid();
    }

    /**
     * @param Model\Order\Address $magentoAddress
     * @return \Ls\Omni\Client\Ecommerce\Entity\Address
     */
    public function convertAddress(Model\Order\Address $magentoAddress)
    {
        // @codingStandardsIgnoreLine
        $omniAddress = new \Ls\Omni\Client\Ecommerce\Entity\Address();
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
     * @param $orderObj
     * @param $param
     * @param $filterKey
     * @return void
     */
    public function getFilterValues($orderObj, $param, $filterKey)
    {
        foreach ($orderObj as $key => $lines) {
            if ($key != $filterKey) {
                continue;
            }
            return $this->getParameterValues($lines, $param);
        }
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
        if (array_key_exists($param, $orderObj->getData())) {
            $value = $orderObj->getData($param);
        }
        return $value;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Set required payment methods for the order
     *
     * @param Order $order
     * @param string $cardId
     * @param string $storeId
     * @return array
     * @throws GuzzleException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setOrderPayments(Model\Order $order, string $cardId, string $storeId)
    {
        $transId = $order->getPayment()->getLastTransId();
        $ccType = $order->getPayment()->getCcType() ? substr($order->getPayment()->getCcType(), 0, 10) : '';
        $cardNumber = $order->getPayment()->getCcLast4();
        $paidAmount = $order->getPayment()->getAmountPaid();
        $authorizedAmount = $order->getPayment()->getAmountAuthorized();
        $preApprovedDate = date('Y-m-d', strtotime('+1 years'));

        $orderPaymentArray = [];
        //TODO change it to $paymentMethod->isOffline() == false when order edit option available for offline payments.
        $paymentCode = $order->getPayment()->getMethodInstance()->getCode();
        $tenderTypeId = $this->getPaymentTenderTypeId($paymentCode);

        $noOrderPayment = $this->paymentLineNotRequiredPaymentMethods($order);

        $shippingMethod = $order->getShippingMethod(true);
        $isClickCollect = false;

        if ($shippingMethod !== null) {
            $carrierCode = $shippingMethod->getData('carrier_code');
            $isClickCollect = $carrierCode == 'clickandcollect';
        }
        $lineNumber = 10000;
        if (!in_array($paymentCode, $noOrderPayment)) {
            // @codingStandardsIgnoreStart
            $orderPayment = $this->createInstance(CustomerOrderCreateCOPaymentV6::class);
            $orderPayment->addData(
                [
                    CustomerOrderCreateCOPaymentV6::STORE_NO => $isClickCollect ? $order->getPickupStore() : $storeId,
                    CustomerOrderCreateCOPaymentV6::LINE_NO => $lineNumber,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_AMOUNT => $order->getGrandTotal(),
                    CustomerOrderCreateCOPaymentV6::TENDER_TYPE => $tenderTypeId,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_VALID_DATE => $preApprovedDate,
                    CustomerOrderCreateCOPaymentV6::EXTERNAL_REFERENCE => $order->getIncrementId(),
                    CustomerOrderCreateCOPaymentV6::CURRENCY_CODE => $order->getOrderCurrency()->getCurrencyCode(),
                    CustomerOrderCreateCOPaymentV6::CURRENCY_FACTOR => 1,
                    CustomerOrderCreateCOPaymentV6::PRE_APPROVED_AMOUNT_LCY => $order->getGrandTotal() * 1
                ]
            );
            // For CreditCard/Debit Card payment  use Tender Type 1 for Cards
            if (!empty($transId)) {
                $orderPayment->addData(
                    [
                        CustomerOrderCreateCOPaymentV6::CARD_TYPE => $ccType,
                        CustomerOrderCreateCOPaymentV6::CARDOR_CUSTOMERNUMBER => $cardNumber,
                        CustomerOrderCreateCOPaymentV6::TOKEN_NO => $transId,
                    ]
                );
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
            $orderPaymentArray[] = $orderPayment;
            $lineNumber += 10000;
        }

        if ($order->getLsPointsSpent()) {
            $tenderTypeId = $this->getPaymentTenderTypeId(LSR::LS_LOYALTYPOINTS_TENDER_TYPE);
            $pointRate = 0;
            $orderPayment = $this->createInstance(CustomerOrderCreateCOPaymentV6::class);
            $orderPayment->addData(
                [
                    CustomerOrderCreateCOPaymentV6::STORE_NO => $isClickCollect ? $order->getPickupStore() : $storeId,
                    CustomerOrderCreateCOPaymentV6::LINE_NO => $lineNumber,
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
            $tenderTypeId = $this->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
            $giftCardCurrencyCode = $order->getOrderCurrency()->getCurrencyCode();

            $orderPayment = $this->createInstance(CustomerOrderCreateCOPaymentV6::class);
            $orderPayment->addData(
                [
                    CustomerOrderCreateCOPaymentV6::STORE_NO => $isClickCollect ? $order->getPickupStore() : $storeId,
                    CustomerOrderCreateCOPaymentV6::LINE_NO => $lineNumber,
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
     * Get payment type id
     *
     * @param $paymentType
     * @return string
     */
    public function getPaymentTypeId($paymentType)
    {
        switch ($paymentType) {
            case 1:
                return PaymentType::PAYMENT;
            case 2:
                return PaymentType::PRE_AUTHORIZATION;
            case 3:
                return PaymentType::REFUND;
            case 4:
                return PaymentType::SHIPPED;
            case 5:
                return PaymentType::COLLECTED;
            case 6:
                return PaymentType::ROUNDING;
            case 7:
                return PaymentType::REFUNDED_ON_P_O_S;
            case 8:
                return PaymentType::VOIDED;
            default:
                return PaymentType::NONE;
        }
    }

    /**
     * Get payment type
     *
     * @param $order
     * @return string
     */
    public function getPaymentType($order)
    {
        $paidAmount       = $order->getPayment()->getAmountPaid();
        $authorizedAmount = $order->getPayment()->getAmountAuthorized();
        if (!empty($paidAmount)) {
            return "1";
        } else {
            if (!empty($authorizedAmount)) {
                return "2";
            } else {
                return "0";
            }
        }
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
        $getSalesHistory = $this->dataHelper->createInstance(
            GetMemContSalesHist_GetMemContSalesHist::class,
            []
        );

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
        return $response && $response->getResponseCode() == "0000" ? $response->getRecords()[0]->getLSCMemberSalesBuffer() : $response;
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
     * @return GetSelectedSalesDoc_GetSelectedSalesDoc|null
     * @throws InvalidEnumException
     */
    public function getOrderDetailsAgainstId($docId, $type = DocumentIdType::ORDER)
    {
        $response = null;
        $typeId   = $this->getOrderTypeId($type);
        $request = $this->createInstance(
            GetSelectedSalesDoc_GetSelectedSalesDoc::class,
            []
        );

        $request->setOperationInput(
            [
                'documentSourceType' => $typeId,
                'documentID' => $docId
            ]
        );

        try {
            $response = $request->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response &&
        $response->getResponsecode() == "0000" &&
        !empty(current((array) $response->getRecords())->getData()) ? current((array) $response->getRecords()) : null;
    }

    /**
     * Get sales order by order id
     *
     * @param $docId
     * @return Entity\GetSalesInfoByOrderId_GetSalesInfoByOrderId|null
     * @throws InvalidEnumException
     */
    public function getSalesOrderByOrderIdNew($docId)
    {
        $operation = $this->createInstance(GetSalesInfoByOrderId_GetSalesInfoByOrderId::class);
        $operation->setOperationInput([
            'customerOrderId' => $docId
        ]);
        $response = null;

        try {
            $response = $operation->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response && $response->getResponseCode() == "0000" ?
            current((array)$response->getRecords()) : $response;
    }

    /**
     * Get sales return details
     *
     * @param string $docId
     * @return Entity\GetSalesReturnById_GetSalesReturnById|null
     */
    public function getReturnDetailsAgainstId(string $docId)
    {
        $operation = $this->createInstance(GetSalesReturnById_GetSalesReturnById::class);
        $operation->setOperationInput([
            'receiptId' => $docId
        ]);
        $response = $operation->execute();

        return $response && $response->getResponseCode() == "0000" ?
            current((array)$response->getRecords()) : $response;
    }

    /**
     * Validate if the order using CardId
     *
     * @param $order
     * @return bool
     */
    public function isAuthorizedForOrder(
        $order
    ) {
        $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        $order = $this->getOrder();
        $orderLscMemberSalesBuffer = $this->getLscMemberSalesBuffer($order);
        $orderCardId = $orderLscMemberSalesBuffer->getMemberCardNo();

        if ($cardId == $orderCardId) {
            return true;
        }
        return false;
    }

    /**
     * Get order header
     *
     * @param $salesEntry
     * @return Entity\LSCMemberSalesBuffer
     */
    public function getLscMemberSalesBuffer($salesEntry)
    {
        return is_array($salesEntry->getLscMemberSalesBuffer()) ?
        current($salesEntry->getLscMemberSalesBuffer()) : $salesEntry->getLscMemberSalesBuffer();
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
     * @return GetSelectedSalesDoc_GetSelectedSalesDoc|Entity\GetSalesInfoByOrderId_GetSalesInfoByOrderId|null
     * @throws InvalidEnumException
     */
    public function fetchOrder($docId, $type)
    {
        $fetchedOrder = null;

        if ($type == 'Receipt') {
            $order = $this->getOrderDetailsAgainstId($docId, $type);
            if ($order) {
                $orderLscMemberSalesBuffer = $this->getLscMemberSalesBuffer($order);
                $docId = !empty($orderLscMemberSalesBuffer->getCustomerDocumentId()) ?
                    $orderLscMemberSalesBuffer->getCustomerDocumentId() : $docId;
                $fetchedOrder = $this->getSalesOrderByOrderIdNew($docId);
            }
        }

        return $fetchedOrder && !empty($fetchedOrder->getData()) ?
            $fetchedOrder : $this->getOrderDetailsAgainstId($docId, $type);
    }

    /**
     * Set LS Central order details in registry.
     *
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
        $lscMemberSalesBuffer = $this->getLscMemberSalesBuffer($salesEntry);
        $documentId = !empty($lscMemberSalesBuffer->getCustomerDocumentId()) ?
            $lscMemberSalesBuffer->getCustomerDocumentId() :
            (!empty($lscMemberSalesBuffer->getDocumentId()) ? $lscMemberSalesBuffer->getDocumentId() : "");
        $order = $this->getOrderByDocumentId($documentId);
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
     * @param $documentId
     * @return array|false|mixed
     */
    public function getOrderByDocumentId($documentId)
    {
        $order = [];
        try {
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
     * Get magento order given increment_id
     *
     * @param $incrementId
     * @return false|mixed|null
     */
    public function getMagentoOrderGivenExternalId($incrementId)
    {
        $order     = null;
        $orderList = $this->orderRepository->getList(
            $this->basketHelper->getSearchCriteriaBuilder()->
            addFilter('increment_id', $incrementId)->create()
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
        $request = $this->dataHelper->createInstance(
            CustomerOrderCancel::class,
            []
        );
        $request->setOperationInput(
            [
                CustomerOrderCancelRequest::CUSTOMER_ORDER_DOCUMENT_ID => $documentId,
                CustomerOrderCancelRequest::SOURCE_TYPE => $storeId
            ]
        );
        try {
            $response = $request->execute($request);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response && $response->getResponsecode() == "0000";
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
        if (!$response) {
            $this->formulateException($order);
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
        } catch (Exception $e) {
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
                    if (!is_array($order)) {
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
        return match ($idType) {
            1 => DocumentIdType::ORDER,
            2 => DocumentIdType::HOSP_ORDER,
            default => DocumentIdType::RECEIPT,
        };
    }

    /**
     * Get order status based on document source type
     *
     * @param $orderObj
     * @return string
     */
    public function getOrderStatus($orderObj)
    {
        $sourceType = $this->getParameterValues($orderObj, "Document Source Type");
        $orderType  = $this->getOrderType($sourceType);
        switch ($orderType) {
            case DocumentIdType::RECEIPT: //Receipt
                return SalesEntryStatus::COMPLETE;
            case DocumentIdType::ORDER: //Order
                return $orderObj->getSaleIsReturnSale() ? SalesEntryStatus::CANCELED : SalesEntryStatus::CREATED;
            case DocumentIdType::HOSP_ORDER: //HOSP ORDER
                return SalesEntryStatus::PROCESSING;
            default:
                return SalesEntryStatus::CREATED;
        }
    }

    /**
     * Get order type based on the provided ID type
     *
     * @param string $type
     * @return int
     */
    public function getOrderTypeId($type)
    {
        return match ($type) {
            DocumentIdType::ORDER => 1,
            DocumentIdType::HOSP_ORDER => 2,
            default => 0,
        };
    }

    /**
     * Get LineType Id
     *
     * @param $lineType
     * @return int
     */
    public function getLineType($lineType)
    {
        return match ($lineType) {
            LineType::ITEM => 0,
            LineType::PAYMENT => 1,
            LineType::SHIPPING => 7,
            default => 0,
        };
    }

    /**
     * Processes order data and updates fields based on order types and conditions.
     *
     * @param array $orders
     * @return array
     */
    public function processOrderData($orders)
    {
        if (!is_array($orders)) {
            $orders = [$orders];
        }
        foreach ($orders as $order) {
            $order['IdType']          = $this->getOrderType($order['Document Source Type']);
            $order['CustomerOrderNo'] = ($order['Customer Document ID']) ?:
                $order['Document ID'];

            switch ($order['IdType']) {
                case DocumentIdType::RECEIPT: //Receipt
                    $order['Status']               = SalesEntryStatus::COMPLETE;
                    $order['ShippingStatus']       = ShippingStatus::SHIPPED;
                    $order['ClickAndCollectOrder'] = ($order['Customer Document ID'] === null ||
                            $order['Customer Document ID'] === '') == false;
                    if (($order['Ship-to Name'] === null || $order['Ship-to Name'] === '')) {
                        $order['Ship-to Name']  = $order['Name'];
                        $order['Ship-to Email'] = $order['Email'];
                    }
                    break;
                case DocumentIdType::ORDER: //Order
                    $order['Status']               = $order['Sale Is Return Sale'] ?
                        SalesEntryStatus::CANCELED : SalesEntryStatus::CREATED;
                    $order['ShippingStatus']       = ShippingStatus::NOT_YET_SHIPPED;
                    $order['CreateAtStoreId']      = $order['Store No.'];
                    $order['ClickAndCollectOrder'] = "Need to implement";
                    break;
                case DocumentIdType::HOSP_ORDER: //HOSP ORDER
                    $order['CreateTime']           = $order['Date Time'];
                    $order['CreateAtStoreId']      = $order['Store No.'];
                    $order['Status']               = SalesEntryStatus::PROCESSING;
                    $order['ShippingStatus']       = ShippingStatus::SHIPPIG_NOT_REQUIRED;
                    if ($order['Ship-to Name'] === null || $order['Ship-to Name'] === '') {
                        $order['Ship-to Name']  = $order['Name'];
                        $order['Ship-to Email'] = $order['Email'];
                    }
                    break;
            }
        }
        return $orders;
    }

    /**
     * Get country name by country code
     *
     * @param string $countryCode
     * @return string
     */
    public function getCountryName($countryCode)
    {
        $country = $this->countryFactory->create()->loadByCode($countryCode);
        return $country->getCountryId();
    }
}
