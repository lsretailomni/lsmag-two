<?php

namespace Ls\Omni\Model\Sales\AdminOrder;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OrderType;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Client\Ecommerce\Entity\Order as CommerceOrder;
use \Ls\Omni\Client\Ecommerce\Entity\OrderEdit as EditOrder;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OrderEditType;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Class for editing order in magento and send order to order edit api
 */
class OrderEdit
{
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @param OrderHelper $orderHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param LSR $LSR
     * @param Data $data
     */
    public function __construct(
        OrderHelper $orderHelper,
        ItemHelper $itemHelper,
        LoggerInterface $logger,
        LSR $LSR
    ) {
        $this->orderHelper = $orderHelper;
        $this->itemHelper  = $itemHelper;
        $this->logger      = $logger;
        $this->lsr         = $LSR;
    }

    /**
     * For sending order edit request
     *
     * @param $request
     * @return Entity\OrderCreateResponse|ResponseInterface
     */
    public function orderEdit($request)
    {
        // @codingStandardsIgnoreLine
        $operation = new Operation\OrderEdit();
        $response  = $operation->execute($request);
        // @codingStandardsIgnoreLine
        return $response;
    }

    /**
     * prepare order edit
     *
     * @param Order $order
     * @param $oneListCalculateResponse
     * @param Order $oldOrder
     * @param $documentId
     * @return EditOrder|void
     */
    public function prepareOrder(Order $order, $oneListCalculateResponse, Order $oldOrder, $documentId)
    {
        try {
            $orderEdit = new EditOrder();
            $orderEdit->setOrderId($documentId);
            $orderEdit->setEditType(OrderEditType::GENERAL);
            $orderObject = new CommerceOrder();
            $orderObject->setStoreId($oneListCalculateResponse->getStoreId());
            $orderObject->setCardId($oneListCalculateResponse->getCardId());
            $orderObject->setEmail($order->getCustomerEmail());

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
            $shippingMethod = $order->getShippingMethod(true);
            //TODO work on condition
            $isClickCollect = false;
            $carrierCode    = '';
            $method         = '';


            /** Entity\ArrayOfOrderPayment $orderPaymentArrayObject */
            // @codingStandardsIgnoreStart
            $orderPaymentArrayObject = new Entity\ArrayOfOrderPayment();
            // @codingStandardsIgnoreEndund
            $orderPaymentArray = [];
            if ($shippingMethod !== null) {
                $carrierCode    = $shippingMethod->getData('carrier_code');
                $method         = $shippingMethod->getData('method');
                $isClickCollect = $carrierCode == 'clickandcollect';
            }

            $orderPaymentArray = $this->setOrderPayments(
                $oldOrder,
                $cardId,
                'refund',
                4,
                $orderPaymentArray
            );
            $orderPaymentArray = $this->setOrderPayments(
                $order,
                $cardId,
                '',
                7,
                $orderPaymentArray
            );

            $orderPaymentArrayObject->setOrderPayment($orderPaymentArray);

            //if the shipping address is empty, we use the contact address as shipping address.
            $contactAddress = $order->getBillingAddress() ? $this->orderHelper->convertAddress(
                $order->getBillingAddress()
            ) : null;
            $shipToAddress  = $order->getShippingAddress() ? $this->orderHelper->convertAddress(
                $order->getShippingAddress()
            ) :
                $contactAddress;

            $orderObject
                ->setId($order->getIncrementId())
                ->setCardId($cardId)
                ->setEmail($customerEmail)
                ->setShipToEmail($customerEmail)
                ->setContactName($customerName)
                ->setShipToName($shipToName)
                ->setContactAddress($contactAddress)
                ->setShipToAddress($shipToAddress)
                ->setStoreId($storeId);
            if ($isClickCollect) {
                $orderObject->setOrderType(OrderType::CLICK_AND_COLLECT);
            } else {
                $orderObject->setOrderType(OrderType::SALE);
                //TODO need to fix the length issue once LS Central allow more then 10 characters.
                $carrierCode = ($carrierCode) ? substr($carrierCode, 0, 10) : "";
                $oneListCalculateResponse->setShippingAgentCode($carrierCode);
                $method = ($method) ? substr($method, 0, 10) : "";
                $orderObject->setShippingAgentServiceCode($method);
            }

            $orderObject->setOrderPayments($orderPaymentArrayObject);
            /** @var Entity\OneListItem[] $orderLinesArray */
            $orderLinesArray = $oneListCalculateResponse->getOrderLines()->getOrderLine();
            /** @var OrderItemInterface[] $olditems */
            $olditems = $oldOrder->getItems();
            /** @var OrderItemInterface[] $newItems */
            $newItems = $order->getItems();
            foreach ($newItems as $item) {
                foreach ($olditems as $oldItem) {
                    if ($item->getSku() == $oldItem->getSku() && $item->getQtyOrdered() > $oldItem->getQtyOrdered()) {
                        $qtyDifference = $item->getQtyOrdered() - $oldItem->getQtyOrdered();
                        list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                            $item->getSku()
                        );
                        foreach ($orderLinesArray as &$orderLine) {
                            if ($orderLine->getItemId() == $itemId &&
                                $orderLine->getVariantId() == $variantId &&
                                $orderLine->getUomId() == $uom
                            ) {
                                $price          = ($orderLine->getPrice() / $orderLine->getQuantity())
                                    * $qtyDifference;
                                $amount         = ($orderLine->getAmount() / $orderLine->getQuantity())
                                    * $qtyDifference;
                                $netPrice       = ($orderLine->getNetPrice() / $orderLine->getQuantity())
                                    * $qtyDifference;
                                $taxAmount      = ($orderLine->getTaxAmount() / $orderLine->getQuantity())
                                    * $qtyDifference;
                                $discountAmount = ($orderLine->getDiscountAmount() / $orderLine->getQuantity())
                                    * $qtyDifference;
                                $itemId         = $orderLine->getItemId();
                                $orderLine->setPrice($orderLine->getPrice() - $price);
                                $orderLine->setAmount($orderLine->getAmount() - $amount);
                                $orderLine->setNetPrice($orderLine->getNetPrice() - $netPrice);
                                $orderLine->setTaxAmount($orderLine->getTaxAmount() - $taxAmount);
                                $orderLine->setDiscountAmount($orderLine->getDiscountAmount() - $discountAmount);
                                $orderLine->setQuantity($orderLine->getQuantity() - $qtyDifference);
                                $lineNumber = (count($orderLinesArray) + 1) * 1000;
                                // @codingStandardsIgnoreLine
                                $lineOrder = new Entity\OrderLine();
                                $lineOrder->setPrice($price)
                                    ->setAmount($amount)
                                    ->setNetPrice($netPrice)
                                    ->setNetAmount($netPrice)
                                    ->setTaxAmount($taxAmount)
                                    ->setItemId($itemId)
                                    ->setLineType(Entity\Enum\LineType::ITEM)
                                    ->setLineNumber($lineNumber)
                                    ->setQuantity($qtyDifference)
                                    ->setDiscountAmount($discountAmount);
                                array_push($orderLinesArray, $lineOrder);
                            }
                        }
                    }
                }
            }
            $orderLinesArray = $this->orderHelper->updateShippingAmount($orderLinesArray, $order);
            if (version_compare($this->lsr->getOmniVersion(), '2023.05.1', '>=')) {
                $orderEdit->setReturnOrderIdOnly(true);
            }

            $orderObject->setOrderLines($orderLinesArray);
            $orderEdit->setRequest($orderObject);
            return $orderEdit;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Set order payments
     *
     * @param Order $order
     * @param $cardId
     * @param $isType
     * @param $startingLineNumber
     * @param $orderPaymentArray
     * @return array
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setOrderPayments(Order $order, $cardId, $isType, $startingLineNumber, $orderPaymentArray)
    {
        $transId          = $order->getPayment()->getLastTransId();
        $ccType           = $order->getPayment()->getCcType() ? substr(
            $order->getPayment()->getCcType(),
            0,
            10
        ) : '';
        $cardNumber       = $order->getPayment()->getCcLast4();
        $paidAmount       = $order->getPayment()->getAmountPaid();
        $authorizedAmount = $order->getPayment()->getAmountAuthorized();
        $preApprovedDate  = date('Y-m-d', strtotime('+1 years'));
        $paymentCode      = ($isType == 'refund') ? $isType : $order->getPayment()->getMethodInstance()->getCode();
        $tenderTypeId     = $this->orderHelper->getPaymentTenderTypeId($paymentCode);

        $noOrderPayment = ['ls_payment_method_pay_at_store', 'free'];

        if (!in_array($paymentCode, $noOrderPayment)) {
            // @codingStandardsIgnoreStart
            $orderPayment = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
            $orderPayment->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
                ->setCurrencyFactor($order->getBaseToOrderRate())
                ->setLineNumber($startingLineNumber)
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
            $tenderTypeId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_LOYALTYPOINTS_TENDER_TYPE);
            $pointRate    = $this->orderHelper->loyaltyHelper->getPointRate();
            // @codingStandardsIgnoreStart
            $orderPaymentLoyalty = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment types.
            $orderPaymentLoyalty->setCurrencyCode('LOY')
                ->setCurrencyFactor($pointRate)
                ->setLineNumber($startingLineNumber + 1)
                ->setCardNumber($cardId)
                ->setExternalReference($order->getIncrementId())
                ->setAmount($order->getLsPointsSpent())
                ->setPreApprovedValidDate($preApprovedDate)
                ->setTenderType($tenderTypeId);
            $orderPaymentArray[] = $orderPaymentLoyalty;
        }
        if ($order->getLsGiftCardAmountUsed()) {
            $tenderTypeId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
            // @codingStandardsIgnoreStart
            $orderPaymentGiftCard = new Entity\OrderPayment();
            // @codingStandardsIgnoreEnd
            //default values for all payment typoes.
            $orderPaymentGiftCard
                ->setCurrencyFactor(1)
                ->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
                ->setAmount($order->getLsGiftCardAmountUsed())
                ->setLineNumber($startingLineNumber + 2)
                ->setCardNumber($order->getLsGiftCardNo())
                ->setAuthorizationCode($order->getLsGiftCardPin())
                ->setExternalReference($order->getIncrementId())
                ->setPreApprovedValidDate($preApprovedDate)
                ->setTenderType($tenderTypeId);
            $orderPaymentArray[] = $orderPaymentGiftCard;
        }

        return $orderPaymentArray;
    }
}
