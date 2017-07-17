<?php
namespace Ls\Omni\Helper;

use \Magento\Framework\App\Helper\Context;
use \Magento\Sales\Model;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Ls\Omni\Client\Loyalty\Entity\Enum;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Helper\BasketHelper;
use Ls\Customer\Model\LSR;

class OrderHelper extends AbstractHelper {

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
    )
    {
        parent::__construct($context);
        $this->order = $order;
        $this->basketHelper = $basketHelper;
        $this->customerSession = $customerSession;
    }

    public function placeOrderById($orderId, Entity\BasketCalcResponse $basketCalcResponse) {
        $this->placeOrder($this->prepareOrder($this->order->load($orderId), $basketCalcResponse));
    }

    /**
     * @param Model\Order $order
     * @param Entity\BasketCalcResponse $basketCalcResponse
     * @return Entity\OrderCreate
     */
    public function prepareOrder(Model\Order $order, Entity\BasketCalcResponse $basketCalcResponse) {
        // TODO: add inline feature again
        //$isInline = LSR::getStoreConfig( LSR::SC_CART_SALESORDER_INLINE ) == LSR_Core_Model_System_Source_Process_Type::ON_DEMAND;
        $isInline = true;

        $shippingMethod = $order->getShippingMethod( TRUE );
        $isClickCollect = $shippingMethod->getData( 'carrier_code' ) == 'clickcollect';

        /** @var Entity\BasketCalcResponse $basketCalcResponse */
        $basketCalcResponse = $this->basketHelper->getOneListCalculation();
        /** @var Entity\BasketLineCalcResponse[] $lines */
        $lines = $basketCalcResponse->getBasketLineCalcResponses()->getBasketLineCalcResponse();

        $entity  = new Entity\Order();

        /** @var Entity\OrderLineCreateRequest[] $orderLinesArray */
        $orderLinesArray = array();
        $orderLinesArrayObject = new Entity\ArrayOfOrderLineCreateRequest();

        /** @var Entity\BasketLineCalcResponse $line */
        foreach ( $lines as $line ) {
            // not actually needed
            // 'barcode_id' => $line->getBarcodeId(),
            // 'coupon' => $line->getCouponCode(),
            $orderLinesArray[] = (new Entity\OrderLineCreateRequest())
                ->setItemId($line->getItemId())
                ->setQuantity($line->getQuantity())
                ->setPrice($line->getPrice())
                ->setDiscountAmount($line->getDiscountAmount())
                ->setDiscountPercent($line->getDiscountPercent())
                ->setNetAmount($line->getNetAmount())
                ->setNetPrice($line->getNetPrice())
                ->setUomId($line->getUom())
                ->setVariantId($line->getVariantId())
                ->setTaxAmount($line->getTAXAmount());
        }

        // TODO: add shipping cost

        $orderLinesArrayObject->setOrderLineCreateRequest($orderLinesArray);
        $entity->setOrderLineCreateRequests($orderLinesArrayObject);

        $discountArrayObject = new Entity\ArrayOfOrderDiscountLineCreateRequest();
        /** @var Entity\OrderDiscountLineCreateRequest[] $discountArray */
        $discountArray = array();

        $lineDiscounts = $line->getBasketLineDiscResponses();
        $discounts = array();
        if (!is_null( $lineDiscounts->getBasketLineDiscResponse() )) {
            /** @var Entity\BasketLineDiscResponse[] $discounts */
            $discounts = $lineDiscounts->getBasketLineDiscResponse();
        }
        if ( count($discounts) > 0 ) {
            /** @var Entity\BasketLineCalcResponse $discount */
            foreach ($discounts as $discount) {
                // not actually needed
                // 'qty' => $discount->getQuantity(),
                # store information from current discount
                $discountArray[] = ( new Entity\OrderDiscountLineCreateRequest() )
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
            $discountArrayObject->setOrderDiscountLineCreateRequest($discountArray);
        }
        $entity->setOrderDiscountLineCreateRequests($discountArrayObject);

        // TODO: add payment, shipping info, user info, anonymous order info
        /*
        $entity
            ->setAnonymousOrder()
            ->setCardId()
            ->setClickAndCollectOrder()
            ->setContactAddress()
            ->setContactId()
            ->setContactName()
            ->setDayPhoneNumber()
            ->setEmail()
            ->setId()
            ->setItemNumberType()
            ->setMobileNumber()
            ->setOrderPaymentCreateRequests()
            ->setPhoneNumber()
            ->setShipToAddress()
            ->setShipToEmail()
            ->setShipToName()
            ->setShipToPhoneNumber()
            ->setShippingAgentCode()
            ->setStoreId();
        */
        $contactId = $this->customerSession->getData( LSR::SESSION_CUSTOMER_LSRID );
        $entity
            ->setContactId($contactId)
            ->setSourceType(Enum\SourceType::E_COMMERCE);

        $request = new Entity\OrderCreate();
        $request->setRequest($entity);

        return $request;
    }

    /**
     * Place the Order directly
     * @param Entity\OrderCreate $request
     * @return Entity\OrderCreateResponse|\Ls\Omni\Client\IResponse
     */
    public function placeOrder(Entity\OrderCreate $request) {
        $operation = new Operation\OrderCreate();
        $response = $operation->execute($request);

        // TODO: check response for success

        /** @var Entity\OneList $oneList */
        // TODO: get current oneList and delete it
        // $this->basketHelper->delete($oneList);

        return $response;
    }


}