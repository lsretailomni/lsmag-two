<?php

namespace Ls\Omni\Helper;

use \Magento\Framework\App\Helper\Context;
use \Magento\Sales\Model;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Ls\Omni\Client\Ecommerce\Entity\Enum;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Helper\BasketHelper;
use Ls\Core\Model\LSR;

class OrderHelper extends AbstractHelper
{

    /** @var Model\Order $order */
    protected $order;
    /** @var \Ls\Omni\Helper\BasketHelper $basketHelper */
    protected $basketHelper;
    /** @var \Magento\Customer\Model\Session $customerSession */
    protected $customerSession;

    public function __construct(
        Context $context,
        Model\Order $order,
        BasketHelper $basketHelper,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->order = $order;
        $this->basketHelper = $basketHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param $orderId
     * @param Entity\BasketCalcResponse $basketCalcResponse
     */
    public function placeOrderById($orderId, Entity\BasketCalcResponse $basketCalcResponse)
    {
        $this->placeOrder(
            $this->prepareOrder(
                $this->order->load($orderId), $basketCalcResponse
            )
        );
    }

    /**
     * @param Model\Order $order
     * @param Entity\BasketCalcResponse $basketCalcResponse
     * @return Entity\OrderCreate
     */
    public function prepareOrder(Model\Order $order, Entity\BasketCalcResponse $basketCalcResponse)
    {


        //$isInline = LSR::getStoreConfig( LSR::SC_CART_SALESORDER_INLINE ) == LSR_Core_Model_System_Source_Process_Type::ON_DEMAND;
        $isInline = true;

        $storeId = $this->basketHelper->getDefaultWebStore();
        #$shipmentFee = $this->getShipmentFeeProdut();
        #$shipmentFeeId = $shipmentFee->getData('lsr_id');
        //TODO get this dynamic.
        $shipmentFeeId = 66010;

        $anonymousOrder = false;

        $shippingMethod = $order->getShippingMethod(true);


        //TODO work on condition
        $isClickCollect = $shippingMethod->getData('carrier_code') == 'clickandcollect';


        /** @var Entity\BasketCalcResponse $basketCalcResponse */
        $basketCalcResponse = $this->basketHelper->getOneListCalculation();
        /** @var Entity\BasketLineCalcResponse[] $lines */


        $entity = new Entity\Order();

        /** @var Entity\OrderLine[] $orderLinesArray */
        $orderLinesArray = [];
        $orderLinesArrayObject = new Entity\ArrayOfOrderLine();

        /** @var Entity\OrderDiscountLine[] $discountArray */
        $discountArray = [];
        $discountArrayObject = new Entity\ArrayOfOrderDiscountLine();

        $lines = $basketCalcResponse->getBasketLineCalcResponses()->getBasketLineCalcResponse();

        /*
         * When there is only one item in the $lines, it does not return in the form of array, it returns in the form of object.
         */

        /** @var Entity\BasketLineCalcResponse $line */
        if (!is_array($lines) and $lines instanceof Entity\BasketLineCalcResponse) {
            $line = $lines;
            // adjust price of shipping item if it is one
            if ($line->getItemId() == $shipmentFeeId) {
                $line->setPrice($order->getShippingAmount())
                    ->setNetPrice($order->getBaseShippingAmount())
                    ->setLineType(Enum\LineType::SHIPPING)
                    ->setQuantity(1)
                    ->setPrice($order->getShippingAmount())
                    ->setDiscountAmount($order->getShippingDiscountAmount());
            }

            if($line->getPrice() > 0) {
                // avoid getting those enttries which does not have any  amount.
                $orderLinesArray[] = (new Entity\OrderLine())
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

                $lineDiscounts = $line->getBasketLineDiscResponses();
                $discounts = [];
                if (!is_null($lineDiscounts->getBasketLineDiscResponse())) {
                    /** @var Entity\BasketLineDiscResponse[] $discounts */
                    $discounts = $lineDiscounts->getBasketLineDiscResponse();
                }
                if (count($discounts) > 0) {
                    /** @var Entity\BasketLineCalcResponse $discount */
                    foreach ($discounts as $discount) {
                        // not actually needed
                        // 'qty' => $discount->getQuantity(),
                        # store information from current discount
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
                }
            }
        } elseif (is_array($lines)) {
            foreach ($lines as $line) {
                if (!$line->getItemId()) {
                    continue;
                }
                // adjust price of shipping item if it is one
                if ($line->getItemId() == $shipmentFeeId) {
                    if($order->getShippingAmount() <= 0){
                        /** adding condition for shipment amount if its zero then does not include it. */
                        continue;

                    }
                    $line->setPrice($order->getShippingAmount())
                        ->setNetPrice($order->getBaseShippingAmount())
                        ->setLineType(Enum\LineType::SHIPPING)
                        ->setQuantity(1)
                        ->setPrice($order->getShippingAmount())
                        ->setDiscountAmount($order->getShippingDiscountAmount());
                }



                $orderLinesArray[] = (new Entity\OrderLine())
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

                $lineDiscounts = $line->getBasketLineDiscResponses();
                $discounts = [];
                if (!is_null($lineDiscounts->getBasketLineDiscResponse())) {
                    /** @var Entity\BasketLineDiscResponse[] $discounts */
                    $discounts = $lineDiscounts->getBasketLineDiscResponse();
                }
                if (count($discounts) > 0) {
                    /** @var Entity\BasketLineCalcResponse $discount */
                    foreach ($discounts as $discount) {
                        // not actually needed
                        // 'qty' => $discount->getQuantity(),
                        # store information from current discount
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
                }
            }
        }



        $orderLinesArrayObject->setOrderLine($orderLinesArray);
        $entity->setOrderLines($orderLinesArrayObject);

        $discountArrayObject->setOrderDiscountLine($discountArray);
        $entity->setOrderDiscountLines($discountArrayObject);

        /** @var Entity\ArrayOfOrderPayment  $orderPaymentArrayObject */
        $orderPaymentArrayObject    =   $this->setOrderPayments($order);

        $entity->setOrderPayments($orderPaymentArrayObject);

        // if guest, then empty cardId and contactId
        $contactId = (!is_null($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID)) ? $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID) : '');
        $cardId = (!is_null($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID)) ? $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID) : '');
        if ($contactId == '' || $cardId == '') {
            // order is for guest so set anonymous Order to true
            $anonymousOrder = true;
        }

        $entity
            ->setContactId($contactId)
            ->setCardId($cardId)
            ->setEmail($this->customerSession->getCustomer()->getData('email'))
            ->setContactName($order->getCustomerName())
            ->setContactAddress($this->convertAddress($order->getBillingAddress()))
            ->setShipToAddress($this->convertAddress($order->getShippingAddress()))
            ->setAnonymousOrder($anonymousOrder)
            ->setClickAndCollectOrder($isClickCollect)
            ->setSourceType(Enum\SourceType::E_COMMERCE)
            ->setStoreId($storeId);

        //For click and collect.
        if ($isClickCollect) {
            $entity->setCollectLocation($order->getPickupStore());
            $entity->setShipClickAndCollect(false);
            $entity->setPaymentStatus('PreApproved');
            $entity->setShippingStatus('NotYetShipped');
        }

        $request = new Entity\OrderCreate();
        $request->setRequest($entity);

        return $request;
    }

    /**
     * Place the Order directly
     * @param Entity\OrderCreate $request
     * @return Entity\OrderCreateResponse|\Ls\Omni\Client\IResponse
     */
    public function placeOrder(Entity\OrderCreate $request)
    {
        $response = null;
        $operation = new Operation\OrderCreate();
        $response = $operation->execute($request);
        return $response ? $response->getResult() : $response;
    }

    /**
     * @param Model\Order\Address $magentoAddress
     * @return Entity\Address
     */
    public function convertAddress(Model\Order\Address $magentoAddress)
    {
        $omniAddress = new Entity\Address();
        foreach ($magentoAddress->getStreet() as $i => $street) {
            //TODO support multiple line address more than 3.
            // stopping the address for multiple street lines, only accepting Address1 and Address2.
            if ($i > 1) {
                break;
            }
            $method = "setAddress" . strval($i + 1);
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

        $orderPaymentArray = [];
        $orderPaymentArrayObject = new Entity\ArrayOfOrderPayment();
        $orderPayment = new Entity\OrderPayment();


        //default values for all payment typoes.
        $orderPayment->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
            ->setCurrencyFactor($order->getBaseToGlobalRate())
            ->setFinalizedAmount($order->getGrandTotal())
            ->setLineNumber('1')
            ->setOrderId($order->getEntityId())
            ->setPreApprovedAmount($order->getGrandTotal());

        // For Cash On Delivery and Cheque use Tender Type as 1

        $orderPayment->setTenderType('0');
        /*
         * Not Supporting at the moment, so all payment methods will be offline,
        if($order->getPayment()->getMethodInstance()->getCode() == 'cashondelivery' || $order->getPayment()->getMethodInstance()->getCode() == 'checkmo'){
            // 0 Mean cash.
        }
         *
         */


        $orderPaymentArray[] = $orderPayment;


         return   $orderPaymentArrayObject->setOrderPayment($orderPaymentArray);

    }


}
