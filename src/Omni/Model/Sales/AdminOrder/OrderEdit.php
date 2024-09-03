<?php

namespace Ls\Omni\Model\Sales\AdminOrder;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OrderType;
use \Ls\Omni\Client\Ecommerce\Entity\OrderCancelExResponse;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Client\Ecommerce\Entity\Order as CommerceOrder;
use \Ls\Omni\Client\Ecommerce\Entity\OrderEdit as EditOrder;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OrderEditType;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\NoSuchEntityException;
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
            $customerOrder = $this->orderHelper->getOrderDetailsAgainstId($documentId);
            $orderEdit     = new EditOrder();
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
            $oldItemsArray = [];
            foreach ($oldItems as $oldItem) {
                $oldItemsArray[$oldItem->getSku()] = $oldItem->getSku();
            }
            $this->removeItemsFromOrder($oldItems, $newItemsArray, $customerOrder, $documentId, $oldOrder);
            $this->addNewItems($newItemsArray, $oldItemsArray, $orderLinesArray, $order);
            $this->updateItemLineNumber($orderLinesArray, $customerOrder);
            $lineOrderArray  = $this->modifyItemQuantity($newItems, $oldItems, $orderLinesArray, $order);
            $orderLinesArray = array_merge($orderLinesArray, $lineOrderArray);
            $orderLinesArray = $this->updateShippingAmount($orderLinesArray, $order, $customerOrder, $oldOrder);
            $orderPaymentArray = $this->setOrderPayments(
                $order,
                $cardId,
                $order->getPayment()->getMethodInstance()->getCode(),
                7 * $order->getEditIncrement() * 10,
                $order->getGrandTotal(),
                $orderPaymentArray
            );
            if (version_compare($this->lsr->getOmniVersion(), '2023.05.1', '>=')) {
                $orderEdit->setReturnOrderIdOnly(true);
            }
            $orderObject->setOrderLines($orderLinesArray);
            $payments = $customerOrder->getPayments()->getSalesEntryPayment();
            foreach ($payments as $payment) {
                if ($payment->getType() == Entity\Enum\PaymentType::PRE_AUTHORIZATION ||
                    $payment->getType() == Entity\Enum\PaymentType::NONE) {
                    $orderPayment = new Entity\OrderPayment();
                    $orderPayment->setPaymentType($payment->getType());
                    $orderPayment->setAuthorizationExpired(true);
                    $orderPayment->setCurrencyCode($payment->getCurrencyCode());
                    $orderPayment->setCardNumber($payment->getCardNo());
                    $orderPayment->setAmount($payment->getAmount());
                    $orderPayment->setTokenNumber($payment->getTokenNumber());
                    $orderPayment->setCardType($payment->getCardType());
                    $orderPayment->setLineNumber($payment->getLineNumber());
                    $orderPayment->setTenderType($payment->getTenderType());
                    $orderUpdatePayment = new Entity\OrderUpdatePayment();
                    $orderUpdatePayment->setOrderId($documentId);
                    $orderUpdatePayment->setStoreId(($oldOrder->getPickupStore()) ? $oldOrder->getPickupStore() :
                        $oneListCalculateResponse->getStoreId());
                    $orderUpdatePayment->setPayment($orderPayment);
                    $orderUpdatePaymentOperation = new Operation\OrderUpdatePayment();
                    $orderUpdatePaymentOperation->execute($orderUpdatePayment);
                }
            }
            $orderPaymentArrayObject->setOrderPayment($orderPaymentArray);
            $orderObject->setOrderPayments($orderPaymentArrayObject);
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
     * @param float $amount
     * @param array $orderPaymentArray
     * @return mixed
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setOrderPayments(
        Order $order,
        $cardId,
        $isType,
        $startingLineNumber,
        $amount,
        $orderPaymentArray
    ) {
        if ($amount > 0) {
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
                    ->setAmount($amount);
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
        }

        return $orderPaymentArray;
    }

    /**
     * Update shipping amount
     *
     * @param $orderLines
     * @param $order
     * @param $customerOrder
     * @param $oldOrder
     * @return mixed
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function updateShippingAmount($orderLines, $order, $customerOrder, $oldOrder)
    {
        $itemsToCancel      = [];
        $shipmentFeeId      = $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID, $order->getStoreId());
        $shipmentTaxPercent = $this->orderHelper->getShipmentTaxPercent($order->getStore());
        $shippingAmount     = $order->getShippingInclTax();
        if ($shippingAmount > 0 && $order->getShippingInclTax() != $oldOrder->getShippingInclTax()) {
            $netPriceFormula = 1 + $shipmentTaxPercent / 100;
            $netPrice        = $shippingAmount / $netPriceFormula;
            $taxAmount       = number_format(($shippingAmount - $netPrice), 2);
            $salesOrderLines = $customerOrder->getLines()->getSalesEntryLine();
            foreach ($salesOrderLines as $line) {
                if ($shipmentFeeId == $line->getItemId()) {
                    // @codingStandardsIgnoreLine
                    $shipmentOrderLine = new Entity\OrderLine();
                    $shipmentOrderLine->setPrice($shippingAmount)
                        ->setAmount($shippingAmount)
                        ->setNetPrice($netPrice)
                        ->setNetAmount($netPrice)
                        ->setTaxAmount($taxAmount)
                        ->setItemId($shipmentFeeId)
                        ->setLineType(Entity\Enum\LineType::ITEM)
                        ->setLineNumber($line->getLineNumber())
                        ->setQuantity(1)
                        ->setDiscountAmount($order->getShippingDiscountAmount());
                    array_push($orderLines, $shipmentOrderLine);
                }
            }
        }

        return $orderLines;
    }

    /**
     * Function to cancel the items that are removed from the order
     *
     * @param $documentId
     * @param $storeId
     * @param $itemsToCancel
     * @return bool|OrderCancelExResponse|ResponseInterface|null
     */
    public function orderCancel($documentId, $storeId, $itemsToCancel)
    {
        $response          = null;
        $request           = new Entity\OrderCancelEx();
        $arrayOfOrderLines = new Entity\ArrayOfOrderCancelLine();
        $orderCancelLine   = new Entity\OrderCancelLine();
        $orderCancelArray  = [];
        foreach ($itemsToCancel as $itemCancel) {
            $orderCancelLine->setLineNo($itemCancel['lineNo']);
            $orderCancelLine->setQuantity($itemCancel['qty']);
            $orderCancelLine->setItemNo($itemCancel['itemId']);
            $orderCancelArray[] = $orderCancelLine;
        }
        $arrayOfOrderLines->setOrderCancelLine($orderCancelArray);
        $request->setOrderId($documentId);
        $request->setStoreId($storeId);
        $request->setUserId("");
        $request->setLines($arrayOfOrderLines);
        $operation = new Operation\OrderCancelEx();
        try {
            $response = $operation->execute($request);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $response ? $response->getOrderCancelExResult() : $response;
    }

    /**
     * Added new items to existing order
     *
     * @param $newItemsArray
     * @param $oldItemsArray
     * @param $orderLinesArray
     * @param $order
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addNewItems($newItemsArray, $oldItemsArray, $orderLinesArray, $order)
    {
        foreach ($newItemsArray as $newItem) {
            if (!in_array($newItem, $oldItemsArray)) {
                list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                    $newItem
                );
                foreach ($orderLinesArray as $line) {
                    if ($itemId == $line->getItemId() && $variantId == $line->getVariantId() &&
                        $uom == $line->getUomId()) {
                        $lineNumber = ((int)$line->getLineNumber() + (int)$order->getEditIncrement());
                        $line->setLineNumber($lineNumber);
                    }
                }
            }
        }
    }

    /**
     * Remove items from exising order
     *
     * @param $oldItems
     * @param $newItemsArray
     * @param $customerOrder
     * @param $documentId
     * @param $oldOrder
     * @return void
     * @throws NoSuchEntityException
     */
    public function removeItemsFromOrder($oldItems, $newItemsArray, $customerOrder, $documentId, $oldOrder)
    {
        $itemsToCancel = [];
        foreach ($oldItems as $oldItem) {
            if (!in_array($oldItem->getSku(), $newItemsArray)) {
                list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                    $oldItem->getSku()
                );
                $orderLines = $customerOrder->getLines()->getSalesEntryLine();
                foreach ($orderLines as $line) {
                    if ($itemId == $line->getItemId() && $variantId == $line->getVariantId() &&
                        $uom == $line->getUomId() && !in_array($line->getLineNumber(), $itemsToCancel)) {
                        $itemsToCancel[$line->getLineNumber()] = [
                            'lineNo' => $line->getLineNumber(),
                            'qty'    => $line->getQuantity(),
                            'itemId' => $line->getItemId(),
                        ];
                    }
                }
            }
        }

        if (!empty($itemsToCancel)) {
            $this->orderCancel($documentId, $customerOrder->getStoreId(), $itemsToCancel);
        }
    }

    /**
     * Increase item quantity
     *
     * @param $newItems
     * @param $oldItems
     * @param $orderLinesArray
     * @param $order
     * @return array
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function modifyItemQuantity($newItems, $oldItems, $orderLinesArray, $order)
    {
        $lineOrderArray = [];
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
                                    $lineNumber     = ((int)$orderLine->getLineNumber()
                                        + (int)$order->getEditIncrement());
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

        return $lineOrderArray;
    }

    /**
     * Decrease item quantity
     *
     * @param $orderLinesArray
     * @param $customerOrder
     * @return void
     */
    public function updateItemLineNumber($orderLinesArray, $customerOrder)
    {
        $orderLines = $customerOrder->getLines()->getSalesEntryLine();
        foreach ($orderLinesArray as $line) {
            foreach ($orderLines as $orderLine) {
                if ($orderLine->getItemId() == $line->getItemId() && $orderLine->getVariantId() == $line->getVariantId()
                    && $orderLine->getUomId() == $line->getUomId()) {
                    $line->setLineNumber($orderLine->getLineNumber());
                }
            }
        }
    }
}
