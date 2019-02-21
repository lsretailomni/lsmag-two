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
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /**
     * OrderHelper constructor.
     * @param Context $context
     * @param Model\Order $order
     * @param BasketHelper $basketHelper
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     */
    public function __construct(
        Context $context,
        Model\Order $order,
        BasketHelper $basketHelper,
        \Magento\Customer\Model\Session\Proxy $customerSession
    ) {
        parent::__construct($context);
        $this->order = $order;
        $this->basketHelper = $basketHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param $orderId
     * @param Entity\BasketCalcResponse $basketCalcResponse
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function placeOrderById($orderId, Entity\BasketCalcResponse $basketCalcResponse)
    {
        $this->placeOrder(
            $this->prepareOrder($this->order->load($orderId), $basketCalcResponse)
        );
    }

    /**
     * @param Model\Order $order
     * @param Entity\BasketCalcResponse $basketCalcResponse
     * @return Entity\OrderCreate
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function prepareOrder(Model\Order $order, Entity\BasketCalcResponse $basketCalcResponse)
    {
        // @codingStandardsIgnoreStart
        //$isInline = LSR::getStoreConfig( LSR::SC_CART_SALESORDER_INLINE ) == LSR_Core_Model_System_Source_Process_Type::ON_DEMAND;
        $isInline = true;
        $storeId = $this->basketHelper->getDefaultWebStore();
        #$shipmentFee = $this->getShipmentFeeProdut();
        #$shipmentFeeId = $shipmentFee->getData('lsr_id');
        //TODO get this dynamic.
        // @codingStandardsIgnoreEnd
        $anonymousOrder = false;
        if ($this->customerSession->isLoggedIn()) {
            $customerEmail = $this->customerSession->getCustomer()->getData('email');
            $customerName = $order->getCustomerName();
            $mobileNumber = $order->getShippingAddress()->getTelephone();
            $contactId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID);
            $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        } else {
            $customerEmail = $order->getCustomerEmail();
            $customerName = $order->getShippingAddress()->getFirstname()." ".
                $order->getShippingAddress()->getLastname();
            $mobileNumber = $order->getShippingAddress()->getTelephone();
            $contactId = $cardId = "";
            $anonymousOrder = true;
        }
        $shippingMethod = $order->getShippingMethod(true);
        //TODO work on condition
        $isClickCollect = $shippingMethod->getData('carrier_code') == 'clickandcollect';
        // @codingStandardsIgnoreLine
        $entity = new Entity\Order();
        /** @var Entity\OrderLine[] $orderLinesArray */
        $orderLinesArray = [];
        // @codingStandardsIgnoreLine
        $orderLinesArrayObject = new Entity\ArrayOfOrderLine();
        /** @var Entity\OrderDiscountLine[] $discountArray */
        $discountArray = [];
        // @codingStandardsIgnoreLine
        $discountArrayObject = new Entity\ArrayOfOrderDiscountLine();
        /** @var Entity\BasketLineCalcResponse[] $lines */
        $lines = $basketCalcResponse->getBasketLineCalcResponses()->getBasketLineCalcResponse();
        $this->populateOrderAndDiscountCollection($lines, $order, $orderLinesArray, $discountArray);
        $orderLinesArrayObject->setOrderLine($orderLinesArray);
        $discountArrayObject->setOrderDiscountLine($discountArray);
        /** @var Entity\ArrayOfOrderPayment $orderPaymentArrayObject */
        $orderPaymentArrayObject = $this->setOrderPayments($order);
        $entity->setOrderDiscountLines($discountArrayObject);
        $entity->setOrderLines($orderLinesArrayObject);
        $entity->setOrderPayments($orderPaymentArrayObject);
        $entity
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
        $entity->setPaymentStatus('PreApproved');
        $entity->setShippingStatus('NotYetShipped');
        //For click and collect.
        if ($isClickCollect) {
            $entity->setCollectLocation($order->getPickupStore());
            $entity->setShipClickAndCollect(false);
        }
        // @codingStandardsIgnoreLine
        $request = new Entity\OrderCreate();
        $request->setRequest($entity);

        return $request;
    }

    /**
     * @param $lines
     * @param $order
     * @param $orderLinesArray
     * @param $discountArray
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function populateOrderAndDiscountCollection($lines, $order, & $orderLinesArray, & $discountArray)
    {
        /*
        * When there is only one item in the $lines,
         * it does not return in the form of array, it returns in the form of object.
        */
        $shipmentFeeId = 66010;
        if (!is_array($lines) and $lines instanceof Entity\BasketLineCalcResponse) {
            /** @var Entity\BasketLineCalcResponse $line */
            $line = $lines;
            // adjust price of shipping item if it is one
            if ($line->getItemId() == $shipmentFeeId && $order->getShippingAmount() > 0) {
                $this->setSpecialPropertiesForShipmentLine($line, $order);
            }
            if ($line->getPrice() > 0) {
                // avoid getting those enttries which does not have any  amount.
                $this->getOrderLineCollectionElement($line, $orderLinesArray);
                $this->populateDiscountArrayForEachLine($line, $discountArray);
            }
        } elseif (is_array($lines)) {
            foreach ($lines as $line) {
                /** @var Entity\BasketLineCalcResponse $line */
                if (!$line->getItemId()) {
                    continue;
                }
                // adjust price of shipping item if it is one

                if ($line->getItemId() == $shipmentFeeId && $order->getShippingAmount() > 0) {
                    $this->setSpecialPropertiesForShipmentLine($line, $order);
                }

                if ($line->getPrice() > 0) {
                    // avoid getting those enttries which does not have any  amount.
                    $this->getOrderLineCollectionElement($line, $orderLinesArray);
                    $this->populateDiscountArrayForEachLine($line, $discountArray);
                }
            }
        }
    }

    /**omnipassword
     * @param $line
     * @param $order
     */
    public function setSpecialPropertiesForShipmentLine(&$line, $order)
    {
        $line->setPrice($order->getShippingAmount())
            ->setNetPrice($order->getBaseShippingAmount())
            ->setLineType(Enum\LineType::SHIPPING)
            ->setQuantity(1)
            ->setDiscountAmount($order->getShippingDiscountAmount());
    }

    /**
     * @param $line
     * @param $orderLinesArray
     */
    public function getOrderLineCollectionElement($line, & $orderLinesArray)
    {
        // @codingStandardsIgnoreStart
        $line = (new Entity\OrderLine())
            ->setItemId($line->getItemId())
            ->setQuantity($line->getQuantity())
            ->setPrice($line->getPrice())
            ->setDiscountAmount($line->getDiscountAmount())
            ->setDiscountPercent($line->getDiscountPercent())
            ->setNetAmount($line->getNetAmount())
            ->setNetPrice($line->getNetPrice())
            ->setUomId($line->getUom())
            ->setVariantId($line->getVariantId())
            ->setTaxAmount($line->getTAXAmount())
            ->setLineNumber($line->getLineNumber());
        $orderLinesArray[] = $line;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param $line
     * @param $discountArray
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function populateDiscountArrayForEachLine($line, & $discountArray)
    {
        $lineDiscounts = $line->getBasketLineDiscResponses();
        $discounts = [];
        if (!($lineDiscounts->getBasketLineDiscResponse() == null)) {
            /** @var Entity\BasketLineDiscResponse[] $discounts */
            $discounts = $lineDiscounts->getBasketLineDiscResponse();
        }
        if (!empty($discounts)) {
            /** @var Entity\BasketLineCalcResponse $discount */
            foreach ($discounts as $discount) {
                // not actually needed
                // @codingStandardsIgnoreStart
                // 'qty' => $discount->getQuantity(),
                # store information from current discount
                // @codingStandardsIgnoreLine
                $discountArray[] = (new Entity\OrderDiscountLine())
                    ->setDescription($discount->getDescription())
                    ->setDiscountAmount($discount->getDiscountAmount())
                    ->setDiscountPercent($discount->getDiscountPercent())
                    ->setDiscountType($discount->getDiscountType())
                    ->setLineNumber($discount->getLineNumber())
                    ->setNo($discount->getNo())
                    ->setOfferNumber($discount->getOfferNumber())
                    ->setPeriodicDiscGroup($discount->getPeriodicDiscGroup())
                    ->setPeriodicDiscType($discount->getPeriodicDiscType());
            }
            // @codingStandardsIgnoreEnd
        }
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
            $method = "setAddress".strval($i + 1);
            $omniAddress->$method($street);
        }
        $omniAddress
            ->setCity($magentoAddress->getCity())
            ->setCountry($magentoAddress->getCountryId())
            ->setStateProvinceRegion($magentoAddress->getRegion())
            ->setPostCode($magentoAddress->getPostcode());

        return $omniAddress;
    }

    /**
     * Please use this funciton to put all condition for different Order Payments:
     * @param Model\Order $order
     * @return Entity\ArrayOfOrderPayment
     */
    public function setOrderPayments(Model\Order $order)
    {

        $transId = $order->getPayment()->getCcTransId();
        $ccType = $order->getPayment()->getCcType();
        $cardNumber = $order->getPayment()->getCcLast4();

        $orderPaymentArray = [];
        // @codingStandardsIgnoreStart
        $orderPaymentArrayObject = new Entity\ArrayOfOrderPayment();
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

        // @codingStandardsIgnoreLine
        /*
         * Not Supporting at the moment, so all payment methods will be offline,
        if($order->getPayment()->getMethodInstance()->getCode() == 'cashondelivery'
        || $order->getPayment()->getMethodInstance()->getCode() == 'checkmo'){
            // 0 Mean cash.
        }
         *
         */
        $orderPaymentArray[] = $orderPayment;

        return $orderPaymentArrayObject->setOrderPayment($orderPaymentArray);
    }
}
