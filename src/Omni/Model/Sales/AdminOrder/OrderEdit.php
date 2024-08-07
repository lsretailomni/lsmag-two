<?php

namespace Ls\Omni\Model\Sales\AdminOrder;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OrderType;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Client\Ecommerce\Entity\Order as CommerceOrder;
use \Ls\Omni\Client\Ecommerce\Entity\OrderEdit as EditOrder;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OrderEditType;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Catalog\Model\Product\Type;
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
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * @param OrderHelper $orderHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param LSR $LSR
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
     * @param object $request
     * @return Entity\OrderEditResponse|\Ls\Omni\Client\ResponseInterface
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
     * Prepare order edit
     *
     * @param Order $order
     * @param object $oneListCalculateResponse
     * @param Order $oldOrder
     * @param string $documentId
     * @return EditOrder|void
     */
    public function prepareOrder(Order $order, $oneListCalculateResponse, Order $oldOrder, $documentId)
    {
        try {
            $orderEdit = new EditOrder();
            $orderEdit->setOrderId($documentId);
            $orderEdit->setEditType(OrderEditType::GENERAL);
            $orderObject = new CommerceOrder();
            $orderObject->setCardId($oneListCalculateResponse->getCardId());
            $orderObject->setEmail($order->getCustomerEmail());

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
                4 * $order->getEditIncrement(),
                $orderPaymentArray
            );
            $orderPaymentArray = $this->setOrderPayments(
                $order,
                $cardId,
                $order->getPayment()->getMethodInstance()->getCode(),
                7 * $order->getEditIncrement(),
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
                ->setStoreId(($oldOrder->getPickupStore()) ? $oldOrder->getPickupStore() :
                    $oneListCalculateResponse->getStoreId());

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
            $lineOrderArray  = [];
            /** @var OrderItemInterface[] $olditems */
            $oldItems = $oldOrder->getItems();
            /** @var OrderItemInterface[] $newItems */
            $newItems      = $order->getItems();
            $newItemsArray = [];
            foreach ($newItems as $newItem) {
                $newItemsArray[$newItem->getSku()] = $newItem->getSku();
            }

            foreach ($newItems as $newItem) {
                if ($newItem->getProductType() == Type::TYPE_SIMPLE) {
                    foreach ($oldItems as $oldItem) {
                        if ($oldItem->getProductType() == Type::TYPE_SIMPLE) {
                            if ($newItem->getSku() == $oldItem->getSku()
                                && $newItem->getQtyOrdered() > $oldItem->getQtyOrdered()) {
                                $qtyDifference = $newItem->getQtyOrdered() - $oldItem->getQtyOrdered();
                                list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                                    $newItem->getSku()
                                );
                                foreach ($orderLinesArray as &$orderLine) {
                                    if ($orderLine->getItemId() == $itemId &&
                                        $orderLine->getVariantId() == $variantId &&
                                        $orderLine->getUomId() == $uom) {
                                        $price          = $orderLine->getPrice();
                                        $amount         = ($orderLine->getAmount() / $orderLine->getQuantity())
                                            * $qtyDifference;
                                        $netPrice       = $orderLine->getNetPrice();
                                        $netAmount      = ($orderLine->getNetAmount() / $orderLine->getQuantity())
                                            * $qtyDifference;
                                        $taxAmount      = ($orderLine->getTaxAmount() / $orderLine->getQuantity())
                                            * $qtyDifference;
                                        $discountAmount = ($orderLine->getDiscountAmount() / $orderLine->getQuantity())
                                            * $qtyDifference;
                                        $lineNumber     = ($orderLine->getLineNumber() + $order->getEditIncrement())
                                            * 100000;
                                        $itemId         = $orderLine->getItemId();
                                        // @codingStandardsIgnoreLine
                                        $lineOrder = new Entity\OrderLine();
                                        $lineOrder->setPrice($price)
                                            ->setAmount($amount)
                                            ->setNetPrice($netPrice)
                                            ->setNetAmount($netAmount)
                                            ->setTaxAmount($taxAmount)
                                            ->setItemId($itemId)
                                            ->setDiscountPercent($orderLine->getDiscountPercent())
                                            ->setUomId($orderLine->getUomId())
                                            ->setVariantId($orderLine->getVariantId())
                                            ->setLineType(Entity\Enum\LineType::ITEM)
                                            ->setLineNumber($lineNumber)
                                            ->setQuantity($qtyDifference)
                                            ->setDiscountAmount($discountAmount);
                                        $lineOrderArray[] = $lineOrder;
                                        $orderLine->setAmount($orderLine->getAmount() - $amount);
                                        $orderLine->setNetAmount($orderLine->getNetAmount() - $netAmount);
                                        $orderLine->setTaxAmount($orderLine->getTaxAmount() - $taxAmount);
                                        $orderLine->setDiscountAmount(
                                            $orderLine->getDiscountAmount() - $discountAmount
                                        );
                                        $orderLine->setQuantity($orderLine->getQuantity() - $qtyDifference);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $orderLinesArray = array_merge($orderLinesArray, $lineOrderArray);
            $orderLinesArray = $this->updateShippingAmount($orderLinesArray, $order, $oldOrder);
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
     * @param string $cardId
     * @param string $isType
     * @param int $startingLineNumber
     * @param int $orderPaymentArray
     * @return mixed
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
        $paymentCode      = $order->getPayment()->getMethodInstance()->getCode();
        $tenderTypeId     = $this->orderHelper->getPaymentTenderTypeId($isType);

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

    /**
     * Update shipping amount
     *
     * @param array $orderLines
     * @param object $order
     * @param object $oldOrder
     * @return mixed
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function updateShippingAmount($orderLines, $order, $oldOrder)
    {
        $shipmentFeeId      = $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID, $order->getStoreId());
        $shippingAmount     = $order->getShippingInclTax() - $oldOrder->getShippingInclTax();
        $shipmentTaxPercent = $this->orderHelper->getShipmentTaxPercent($order->getStore());

        if (!empty($shipmentTaxPercent) && $shippingAmount > 0) {
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
                ->setLineNumber(1000000)
                ->setQuantity(1)
                ->setDiscountAmount($order->getShippingDiscountAmount());
            array_push($orderLines, $shipmentOrderLine);
        }

        return $orderLines;
    }
}
