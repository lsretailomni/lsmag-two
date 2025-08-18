<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Sales\AdminOrder;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\CustomerOrderCancel as CustomerOrderCancelRequest;
use \Ls\Omni\Client\Ecommerce\Entity\RootCustomerOrderEdit;
use \Ls\Omni\Client\Ecommerce\Operation\CustomerOrderCancel;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\Data as OmniHelper;
use \Ls\Omni\Client\Ecommerce\Entity\OrderEdit as EditOrder;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\RootCustomerOrderCancel;
use \Ls\Omni\Client\Ecommerce\Entity\CustomerOrderCancelCOLine;
use \Ls\Omni\Client\Ecommerce\Entity\CustomerOrderStatusLog;
use \Ls\Omni\Client\Ecommerce\Entity\COEditDiscountLine;
use \Ls\Omni\Client\Ecommerce\Entity\COEditLine;
use \Ls\Omni\Client\Ecommerce\Entity\COEditPayment;
use \Ls\Omni\Client\Ecommerce\Entity\COEditHeader;
use \Ls\Omni\Client\Ecommerce\Entity\CustomerOrderEdit as CustomerOrderEditEntity;
use \Ls\Omni\Client\Ecommerce\Operation\CustomerOrderEdit;
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
     * @param OrderHelper $orderHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param LSR $lsr
     * @param LoyaltyHelper $loyaltyHelper
     * @param OmniHelper $omniHelper
     */
    public function __construct(
        public OrderHelper $orderHelper,
        public ItemHelper $itemHelper,
        public LoggerInterface $logger,
        public LSR $lsr,
        public LoyaltyHelper $loyaltyHelper,
        public OmniHelper $omniHelper
    ) {
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
        $operation = $this->omniHelper->createInstance(CustomerOrderEdit::class);
        $operation->setOperationInput(
            [
                CustomerOrderEditEntity::CUSTOMER_ORDER_EDIT_XML => $request,
                CustomerOrderEditEntity::CUSTOMER_ORDER_ID => $request->getCOEditHeader()->getDocumentId(),
                CustomerOrderEditEntity::CO_EDIT_TYPE => 1
            ]
        );
        $response  = $operation->execute($request);
        // @codingStandardsIgnoreLine
        return $response && $response->getResponsecode() == "0000" ? $response : null;
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
            $isClickCollect = false;
            $carrierCode    = '';
            $method         = '';
            $rootCustomerOrderEdit = $this->orderHelper->createInstance(
                RootCustomerOrderEdit::class
            );
            $customerOrder  = $this->orderHelper->getOrderDetailsAgainstId($documentId);
            $cardId        = current((array)$oneListCalculateResponse->getMobiletransaction())->getMembercardno();
            $customerName   = $order->getBillingAddress()->getFirstname() . ' ' .
                $order->getBillingAddress()->getLastname();
            $customerEmail  = $order->getCustomerEmail();
            if ($order->getShippingAddress()) {
                $shipToName = $order->getShippingAddress()->getFirstname() . ' ' .
                    $order->getShippingAddress()->getLastname();
            } else {
                $shipToName = $customerName;
            }
            $shippingMethod = $order->getShippingMethod(true);
            if ($shippingMethod !== null) {
                $carrierCode    = $shippingMethod->getData('carrier_code');
                $method         = $shippingMethod->getData('method');
                $isClickCollect = $carrierCode == 'clickandcollect';
            }
            $shipOrder = (!$isClickCollect) ? true : false;
            //TODO need to fix the length issue once LS Central allow more then 10 characters.
            $carrierCode = ($carrierCode) ? substr($carrierCode, 0, 10) : "";
            $method = ($method) ? substr($method, 0, 10) : "";
            $createdAtStore = ($oldOrder->getPickupStore()) ? $oldOrder->getPickupStore() :
                current((array)$oneListCalculateResponse->getMobiletransaction())->getStoreId();
            
            $orderEditHeader      = $this->omniHelper->createInstance(COEditHeader::class);
            $orderEditHeader->addData(
                [
                    COEditHeader::DOCUMENT_ID => $documentId,
                    COEditHeader::EXTERNAL_ID => strtoupper($order->getIncrementId()),
                    COEditHeader::MEMBER_CARD_NO => $cardId,
                    COEditHeader::CUSTOMER_NO => 44090,
                    COEditHeader::NAME => $customerName,
                    COEditHeader::SOURCE_TYPE => "1",
                    COEditHeader::ADDRESS => $order->getBillingAddress()->getStreetLine(1),
                    COEditHeader::ADDRESS2 => $order->getBillingAddress()->getStreetLine(2),
                    COEditHeader::CITY => $order->getBillingAddress()->getCity(),
                    COEditHeader::COUNTY => $order->getBillingAddress()->getRegion(),
                    COEditHeader::POST_CODE => $order->getBillingAddress()->getPostcode(),
                    COEditHeader::COUNTRY_REGION_CODE => $order->getBillingAddress()->getCountryId(),
                    COEditHeader::PHONE_NO => $order->getBillingAddress()->getTelephone(),
                    COEditHeader::EMAIL => $customerEmail,
                    COEditHeader::SHIP_TO_NAME => $shipToName,
                    COEditHeader::SHIP_TO_ADDRESS => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getStreetLine(1) :
                        $order->getBillingAddress()->getStreetLine(1),
                    COEditHeader::SHIP_TO_ADDRESS2 => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getStreetLine(2) :
                        $order->getBillingAddress()->getStreetLine(2),
                    COEditHeader::SHIP_TO_CITY => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getCity() :
                        $order->getBillingAddress()->getCity(),
                    COEditHeader::SHIP_TO_COUNTY => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getRegion() :
                        $order->getBillingAddress()->getRegion(),
                    COEditHeader::SHIP_TO_POST_CODE => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getPostcode() :
                        $order->getBillingAddress()->getPostcode(),
                    COEditHeader::SHIP_TO_COUNTRY_REGION_CODE => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getCountryId() :
                        $order->getBillingAddress()->getCountryId(),
                    COEditHeader::SHIP_TO_PHONE_NO => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getTelephone() :
                        $order->getBillingAddress()->getTelephone(),
                    COEditHeader::SHIP_TO_EMAIL => $order->getShippingAddress() ?
                        $order->getShippingAddress()->getEmail() :
                        $order->getBillingAddress()->getEmail(),
                    COEditHeader::SHIP_ORDER => $shipOrder,
                    COEditHeader::SHIPPING_AGENT_CODE => $carrierCode,
                    COEditHeader::SHIPPING_AGENT_SERVICE_CODE => $method,
                    COEditHeader::CREATED_AT_STORE => $createdAtStore
                ]
            );

            $rootCustomerOrderEdit->setCOEditHeader($orderEditHeader);
            
            /** @var Entity\OneListItem[] $orderLinesArray */
            $orderLinesArray = $oneListCalculateResponse->getMobiletransactionline();
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
            $coEditLines = $this->generateAndAddNewItemLines(
                $newItemsArray,
                $oldItemsArray,
                $orderLinesArray,
                $order,
                $createdAtStore,
                $customerOrder,
                $documentId
            );
            $this->updateItemLineNumber($coEditLines, $customerOrder);
            $lineOrderArray  = $this->modifyItemQuantity(
                $newItems,
                $oldItems,
                $orderLinesArray,
                $order,
                $createdAtStore,
                $documentId
            );
            $coEditLines = array_merge($coEditLines, $lineOrderArray);
            $coEditLines = $this->updateShippingAmount($coEditLines, $order, $customerOrder, $oldOrder);
            
            $rootCustomerOrderEdit->setCoeditline($coEditLines);
            
            //Set discount lines
            $orderDiscountLines     = $oneListCalculateResponse->getMobiletransdiscountline();
            $orderEditDiscountLines = [];
            if ($orderDiscountLines && count($orderDiscountLines) > 0) {
                foreach ($orderDiscountLines as $orderDiscountLine) {
                    $coDiscountLine = $this->orderHelper->createInstance(
                        COEditDiscountLine::class
                    );

                    $periodicDiscountGroup = $orderDiscountLine->getPeriodicDiscountGroup()
                        ?: $orderDiscountLine->getOfferNo();

                    $coDiscountLine->addData(
                        [
                            COEditDiscountLine::DOCUMENT_ID => $documentId,
                            COEditDiscountLine::LINE_NO => $orderDiscountLine->getLineNumber(),
                            COEditDiscountLine::ENTRY_NO => $orderDiscountLine->getNo(),
                            COEditDiscountLine::DISCOUNT_TYPE => $orderDiscountLine->getDiscountType(),
                            COEditDiscountLine::OFFER_NO => $orderDiscountLine->getOfferNo(),
                            COEditDiscountLine::PERIODIC_DISC_TYPE => $orderDiscountLine->getPeriodicDiscountType(),
                            COEditDiscountLine::PERIODIC_DISC_GROUP => $periodicDiscountGroup,
                            COEditDiscountLine::DESCRIPTION => $orderDiscountLine->getDescription()
                        ]
                    );
                    $orderEditDiscountLines[] = $coDiscountLine;
                }
                $rootCustomerOrderEdit->setCoeditdiscountline($orderEditDiscountLines);
                
            }
            
            /** Entity\ArrayOfOrderPayment $orderPaymentArrayObject */
            // @codingStandardsIgnoreStart
            $orderEditPaymentLines      = $this->omniHelper->createInstance(COEditPayment::class);
            // @codingStandardsIgnoreEndund
            $orderPaymentArray = [];
            $payments = $customerOrder->getLscMemberSalesDocLine();
            $startingLineNumber = 10000 + (7 * $order->getEditIncrement() * 10);
            $orderPaymentArray = $this->setOrderPayments(
                $order,
                $oldOrder,
                $cardId,
                $order->getPayment()->getMethodInstance()->getCode(),
                $startingLineNumber,
                $order->getGrandTotal(),
                $orderPaymentArray,
                $payments,
                $createdAtStore
            );
            $orderEditPaymentLines->addData($orderPaymentArray);
            $rootCustomerOrderEdit->setCoeditpayment($orderEditPaymentLines);
            return $rootCustomerOrderEdit;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Set order payments
     *
     * @param Order $order
     * @param Order $oldOrder
     * @param string $cardId
     * @param string $isType
     * @param int $startingLineNumber
     * @param float $amount
     * @param array $orderPaymentArray
     * @param array $payments,
     * @param string $createdAtStore
     * @return mixed
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setOrderPayments(
        Order $order,
        Order $oldOrder,
        $cardId,
        $isType,
        $startingLineNumber,
        $amount,
        $orderPaymentArray,
        $payments,
        $createdAtStore
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
//                $orderPayment = new Entity\OrderPayment();
                // @codingStandardsIgnoreEnd
                //default values for all payment types.
//                $orderPayment->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
//                    ->setCurrencyFactor($order->getBaseToOrderRate())
//                    ->setLineNumber($startingLineNumber)
//                    ->setExternalReference($order->getIncrementId())
//                    ->setAmount($amount);
                $orderPayment = $this->orderHelper->createInstance(COEditPayment::class);
                $orderPayment->addData(
                    [
                        COEditPayment::CURRENCY_CODE => $order->getOrderCurrency()->getCurrencyCode(),
                        COEditPayment::CURRENCY_FACTOR => $order->getBaseToOrderRate(),
                        COEditPayment::LINE_NO => $startingLineNumber,
                        COEditPayment::EXTERNAL_REFERENCE => $order->getIncrementId(),
                        COEditPayment::PRE_APPROVED_AMOUNT => $amount,
                        COEditPayment::PRE_APPROVED_AMOUNT_LCY => $amount * $order->getBaseToOrderRate(),
                    ]
                );
                
                // For CreditCard/Debit Card payment use Tender Type 1 for Cards
                if (!empty($transId)) {
                    $orderPayment->addData(
                        [
                            COEditPayment::CARD_TYPE => $ccType,
                            COEditPayment::CARDOR_CUSTOMERNUMBER => $cardNumber,
                            COEditPayment::TOKEN_NO => $transId
                        ]
                    );
//                    $orderPayment->setCardType($ccType);
//                    $orderPayment->setCardNumber($cardNumber);
//                    $orderPayment->setTokenNumber($transId);
                    if (!empty($paidAmount)) {
                        $orderPayment->addData(
                            [
                                COEditPayment::TYPE => "1",
                            ]
                        );
                    } else {
                        if (!empty($authorizedAmount)) {
                            $orderPayment->addData(
                                [
                                    COEditPayment::TYPE => "2",
                                    //Entity\Enum\PaymentType::PRE_AUTHORIZATION,
                                ]
                            );
                        } else {
                            $orderPayment->addData(
                                [
                                    COEditPayment::TYPE => "0",
                                    //Entity\Enum\PaymentType::NONE
                                ]
                            );
                        }
                    }
//                    if (!empty($paidAmount)) {
//                        $orderPayment->setPaymentType(Entity\Enum\PaymentType::PAYMENT);
//                    } else {
//                        if (!empty($authorizedAmount)) {
//                            $orderPayment->setPaymentType(Entity\Enum\PaymentType::PRE_AUTHORIZATION);
//                        } else {
//                            $orderPayment->setPaymentType(Entity\Enum\PaymentType::NONE);
//                        }
//                    }
                }
                $orderPayment->addData(
                    [
                        COEditPayment::TENDER_TYPE => $tenderTypeId,
                        COEditPayment::PRE_APPROVED_VALID_DATE => $preApprovedDate,
                    ]
                );
//                $orderPayment->setTenderType($tenderTypeId);
//                $orderPayment->setPreApprovedValidDate($preApprovedDate);
                $orderPaymentArray[] = $orderPayment;
            }

            if ($order->getLsPointsSpent()) {
                $tenderTypeId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_LOYALTYPOINTS_TENDER_TYPE);
                $pointRate    = $this->orderHelper->loyaltyHelper->getPointRate();
                // @codingStandardsIgnoreStart
//                $orderPaymentLoyalty = new Entity\OrderPayment();
                $orderPaymentLoyalty = $this->orderHelper->createInstance(COEditPayment::class);
                // @codingStandardsIgnoreEnd
                //default values for all payment types.
                $orderPaymentLoyalty->addData(
                    [
                        COEditPayment::CURRENCY_CODE => 'LOY',
                        COEditPayment::CURRENCY_FACTOR => $pointRate,
                        COEditPayment::LINE_NO => $startingLineNumber + 1,
                        COEditPayment::CARDOR_CUSTOMERNUMBER => $cardId,
                        COEditPayment::EXTERNAL_REFERENCE => $order->getIncrementId(),
                        COEditPayment::PRE_APPROVED_AMOUNT => $order->getLsPointsSpent(),
                        COEditPayment::PRE_APPROVED_VALID_DATE => $preApprovedDate,
                        COEditPayment::TENDER_TYPE => $tenderTypeId
                    ]
                );
//                $orderPaymentLoyalty->setCurrencyCode('LOY')
//                    ->setCurrencyFactor($pointRate)
//                    ->setLineNumber($startingLineNumber + 1)
//                    ->setCardNumber($cardId)
//                    ->setExternalReference($order->getIncrementId())
//                    ->setAmount($order->getLsPointsSpent())
//                    ->setPreApprovedValidDate($preApprovedDate)
//                    ->setTenderType($tenderTypeId);
                $orderPaymentArray[] = $orderPaymentLoyalty;
            }
            if ($order->getLsGiftCardAmountUsed()) {
                $tenderTypeId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
                // @codingStandardsIgnoreStart
//                $orderPaymentGiftCard = new Entity\OrderPayment();
                $orderPaymentGiftCard = $this->orderHelper->createInstance(COEditPayment::class);
                // @codingStandardsIgnoreEnd
                //default values for all payment types.
                $orderPaymentGiftCard->addData(
                    [
                        COEditPayment::CURRENCY_FACTOR => 1,
                        COEditPayment::CURRENCY_CODE => $order->getOrderCurrency()->getCurrencyCode(),
                        COEditPayment::PRE_APPROVED_AMOUNT => $order->getLsGiftCardAmountUsed(),
                        COEditPayment::LINE_NO => $startingLineNumber + 2,
                        COEditPayment::CARDOR_CUSTOMERNUMBER => $order->getLsGiftCardNo(),
                        COEditPayment::AUTHORIZATION_CODE => $order->getLsGiftCardPin(),
                        COEditPayment::EXTERNAL_REFERENCE => $order->getIncrementId(),
                        COEditPayment::PRE_APPROVED_VALID_DATE => $preApprovedDate,
                        COEditPayment::TENDER_TYPE => $tenderTypeId
                    ]
                );
//                $orderPaymentGiftCard
//                    ->setCurrencyFactor(1)
//                    ->setCurrencyCode($order->getOrderCurrency()->getCurrencyCode())
//                    ->setAmount($order->getLsGiftCardAmountUsed())
//                    ->setLineNumber($startingLineNumber + 2)
//                    ->setCardNumber($order->getLsGiftCardNo())
//                    ->setAuthorizationCode($order->getLsGiftCardPin())
//                    ->setExternalReference($order->getIncrementId())
//                    ->setPreApprovedValidDate($preApprovedDate)
//                    ->setTenderType($tenderTypeId);
                $orderPaymentArray[] = $orderPaymentGiftCard;
            }

            if (is_array($payments) && count($payments) > 0) {
                $paymentType  = $this->orderHelper->getPaymentType($order);
                $lineNo = 0;
                foreach ($payments as $payment) {
                    if ($payment->getEntryType() == 1 && ($paymentType == "2" ||
                            $paymentType == "0")) {
                        $paymentCode  = $oldOrder->getPayment()->getMethodInstance()->getCode();
                        $tenderTypeId = $this->orderHelper->getPaymentTenderTypeId($paymentCode);
                        
                        $lineNo += 10000;
                        $orderPaymentUpdate = $this->orderHelper->createInstance(COEditPayment::class);
                        $orderPaymentUpdate->addData(
                            [
                                COEditPayment::DOCUMENT_ID             => $oldOrder->getDocumentId(),
                                COEditPayment::STORE_NO                => $createdAtStore,
                                COEditPayment::LINE_NO                 => $lineNo,
                                COEditPayment::TYPE                    => $paymentType,
                                COEditPayment::TENDER_TYPE             => (int)$tenderTypeId,
                                COEditPayment::CARD_TYPE               => $payment->getCardType(),
                                COEditPayment::CURRENCY_CODE           => $payment->getCurrencyCode(),
                                COEditPayment::CURRENCY_FACTOR         => $payment->getCurrencyFactor(),
                                COEditPayment::AUTHORIZATION_CODE      => "",
                                COEditPayment::AUTHORIZATION_EXPIRED   => true,
                                COEditPayment::TOKEN_NO                => $oldOrder->getPayment()->getLastTransId(),
                                COEditPayment::CARDOR_CUSTOMERNUMBER   => $payment->getCardOrAccount()
                            ]
                        );
                        if ($paymentType == "2") {
                            $orderPaymentUpdate->addData(
                                [
                                    COEditPayment::PRE_APPROVED_AMOUNT => $payment->getAmount(),
                                    COEditPayment::PRE_APPROVED_AMOUNT_LCY =>
                                        $payment->getCurrencyFactor() * $payment->getAmount()
                                ]
                            );
                        } else {
                            $orderPaymentUpdate->addData(
                                [
                                    COEditPayment::FINALIZED_AMOUNT => $payment->getAmount(),
                                    COEditPayment::FINALIZED_AMOUNT_LCY =>
                                        $payment->getCurrencyFactor() * $payment->getAmount()
                                ]
                            );
                        }

                        $orderPaymentArray[] = $orderPaymentUpdate;
                    }
                }
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
        $shipmentFeeId      = $this->lsr->getStoreConfig(LSR::LSR_SHIPMENT_ITEM_ID, $order->getStoreId());
        $shipmentTaxPercent = $this->orderHelper->getShipmentTaxPercent($order->getStore());
        $shippingAmount     = $order->getShippingInclTax();
        $storeId            = $customerOrder->getLscMemberSalesBuffer()->getStoreNo();
        if ($shippingAmount > 0 && $order->getShippingInclTax() != $oldOrder->getShippingInclTax()) {
            $netPriceFormula = 1 + $shipmentTaxPercent / 100;
            $netPrice        = $shippingAmount / $netPriceFormula;
            $taxAmount       = number_format(($shippingAmount - $netPrice), 2);
            $salesOrderLines = $customerOrder->getLscMemberSalesDocLine();
            
            foreach ($salesOrderLines as $line) {
                if ($shipmentFeeId == $line->getNumber()) {
                    // @codingStandardsIgnoreLine
                    $coEditLine = $this->orderHelper->createInstance(
                        //MobileTransaction::class,
                        COEditLine::class
                    );
                    $coEditLine->addData([
                        COEditLine::LINE_NO => $line->getLineNo(),
                        COEditLine::LINE_TYPE => 0,
                        COEditLine::STATUS => "",
                        COEditLine::NUMBER => $shipmentFeeId,
                        COEditLine::NET_PRICE => $netPrice,
                        COEditLine::PRICE => $shippingAmount,
                        COEditLine::QUANTITY => 1,
                        COEditLine::NET_AMOUNT => $netPrice,
                        COEditLine::VAT_AMOUNT => $taxAmount,
                        COEditLine::AMOUNT => $shippingAmount,
                        COEditLine::STORE_NO => $storeId
                    ]);
                    array_push($orderLines, $coEditLine);
                }
            }
        }

        return $orderLines;
    }

    /**
     *  Function to cancel the items that are removed from the order
     *
     * @param $documentId
     * @param $storeId
     * @param $itemsToCancel
     * @return void
     */
    public function orderCancel($documentId, $storeId, $itemsToCancel)
    {
        $response          = null;
        $cancelRequest = $this->prepareCancelItemRequest($documentId, $storeId, $itemsToCancel);
        
            $request = $this->orderHelper->createInstance(
                CustomerOrderCancel::class
            );
            $request->setOperationInput(
                [
                    CustomerOrderCancelRequest::ERROR_TEXT => "",
                    CustomerOrderCancelRequest::RESPONSE_CODE => "",
                    CustomerOrderCancelRequest::CUSTOMER_ORDER_DOCUMENT_ID => $documentId,
                    CustomerOrderCancelRequest::SOURCE_TYPE => $storeId,
                    CustomerOrderCancelRequest::CUSTOMER_ORDER_CANCEL_XML => $cancelRequest
                ]
            );

        try {
            $response = $request->execute($request);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $response && $response->getResponsecode() == "0000" ? $response->getResponsecode() : $response;
    }

    /**
     *  Prepare cancel request by Line No
     *
     * @param $documentId
     * @param $storeId
     * @param $itemsToCancel
     * @return mixed
     */
    public function prepareCancelItemRequest($documentId, $storeId, $itemsToCancel)
    {
        $rootCustomerOrderCancel = $this->orderHelper->createInstance(
            RootCustomerOrderCancel::class
        );

        $customerOrderStatusLog = $this->orderHelper->createInstance(
            CustomerOrderStatusLog::class
        );

        $customerOrderStatusLog->addData(
            [
                CustomerOrderStatusLog::STORE_NO => $storeId
            ]
        );

        $customerOrderCancelCOLine = $this->orderHelper->createInstance(
            CustomerOrderCancelCOLine::class
        );
        foreach ($itemsToCancel as $itemCancel) {
            $customerOrderCancelCOLine->addData(
                [
                    CustomerOrderCancelCOLine::LINE_NO => $itemCancel['lineNo'],
                    CustomerOrderCancelCOLine::QUANTITY => $itemCancel['qty'],
                    CustomerOrderCancelCOLine::DOCUMENT_ID => $documentId
                ]
            );
        }

        $rootCustomerOrderCancel->setCustomerorderstatuslog($customerOrderStatusLog);
        $rootCustomerOrderCancel->setCustomerordercancelcoline($customerOrderCancelCOLine);
        
        return $rootCustomerOrderCancel;
    }

    /**
     * Added new items to existing order
     *
     * @param $newItemsArray
     * @param $oldItemsArray
     * @param $orderLinesArray
     * @param $order
     * @param $createdAtStore
     * @param $customerOrder
     * @param $documentId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generateAndAddNewItemLines(
        $newItemsArray,
        $oldItemsArray,
        $orderLinesArray,
        $order,
        $createdAtStore,
        $customerOrder,
        $documentId
    ) {
        $orderEditCoLines = [];
        foreach ($orderLinesArray as $line) {
            if ($line->getLinetype() == 0) {
                $coEditLine = $this->orderHelper->createInstance(
                    COEditLine::class
                );
                $amount = ($line->getPrice() * $line->getQuantity()) - $line->getDiscountamount();
                $coEditLine->addData([
                    COEditLine::LINE_NO             => $line->getLineNo(),
                    COEditLine::LINE_TYPE           => $line->getLineType(),
                    COEditLine::STATUS              => "",
                    COEditLine::NUMBER              => $line->getNumber(),
                    COEditLine::VARIANT_CODE        => $line->getVariantCode(),
                    COEditLine::UNITOF_MEASURE_CODE => $line->getUomid(),
                    COEditLine::NET_PRICE           => $line->getNetPrice(),
                    COEditLine::PRICE               => $line->getPrice(),
                    COEditLine::QUANTITY            => $line->getQuantity(),
                    COEditLine::DISCOUNT_AMOUNT     => $line->getDiscountAmount(),
                    COEditLine::DISCOUNT_PERCENT    => $line->getDiscountPercent(),
                    COEditLine::NET_AMOUNT          => $line->getNetAmount(),
                    COEditLine::VAT_AMOUNT          => $line->getTaxAmount(),
                    COEditLine::AMOUNT              => $amount,
                    COEditLine::STORE_NO            => $createdAtStore,
                    COEditLine::EXTERNAL_ID         => $order->getIncrementId(),
                    COEditLine::DOCUMENT_ID         => $documentId,
                    COEditLine::RETAIL_IMAGE_ID     => "",
                ]);

                $orderEditCoLines[] = $coEditLine;
            }
        }

        // Step 1: Get the max existing line number
        $maxLineNo = 0;
        $orderLines = $customerOrder->getLscMemberSalesDocLine();
        foreach ($orderLines as $line) {
            if ($line->getLineType() == 0
                && (int)$line->getLineNo() > $maxLineNo
            ) {
                $maxLineNo = (int)$line->getLineNo();
            }
        }
        
        foreach ($newItemsArray as $newItem) {
            if (!in_array($newItem, $oldItemsArray)) {
                list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                    $newItem
                );
                foreach ($orderEditCoLines as $line) {
                    if ($itemId == $line->getNumber() && $variantId == $line->getVariantCode() &&
                        $uom == $line->getUnitofmeasurecode()) {
                        $lineNumber = ($maxLineNo + 10000);
                        $line->setLineNo($lineNumber);
                        $line->setRetailImageId("NEW_COLINE_INDICATOR"); //To indicate as new line item
                        
                    }
                }
            }
        }

        return $orderEditCoLines;
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
                $orderLines = $customerOrder->getLscMemberSalesDocLine();
                foreach ($orderLines as $line) {
                    if ($itemId == $line->getNumber() && $variantId == $line->getVariantCode() &&
                        $uom == $line->getUnitOfMeasure() && !in_array($line->getLineNo(), $itemsToCancel)) {
                        $itemsToCancel[$line->getLineNumber()] = [
                            'lineNo' => $line->getLineNo(),
                            'qty'    => $line->getQuantity(),
                            'itemId' => $line->getNumber(),
                        ];
                    }
                }
            }
        }

        if (!empty($itemsToCancel)) {
            $storeId = $customerOrder->getLscMemberSalesBuffer()->getStoreNo();
            $this->orderCancel($documentId, $storeId, $itemsToCancel);
        }
    }

    /**
     * Increase item quantity
     *
     * @param $newItems
     * @param $oldItems
     * @param $orderLinesArray
     * @param $order
     * @param $createdAtStore
     * @param $documentId
     * @return array
     * @throws NoSuchEntityException
     */
    public function modifyItemQuantity(
        $newItems,
        $oldItems,
        $orderLinesArray,
        $order,
        $createdAtStore,
        $documentId
    ) {
        $orderEditQtyUpdateCoLines = [];
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
                                if ($orderLine->getNumber() == $itemId &&
                                    $orderLine->getVariantCode() == $variantId &&
                                    $orderLine->getUomId() == $uom) {
                                    $price          = $orderLine->getPrice();
                                    $amount         = ($orderLine->getPrice() * $qtyDifference) -
                                        ($orderLine->getDiscountAmount() / $orderLine->getQuantity());
                                    $netPrice       = $orderLine->getNetPrice();
                                    $netAmount      = ($orderLine->getNetAmount() / $orderLine->getQuantity())
                                        * $qtyDifference;
                                    $taxAmount      = ($orderLine->getTaxAmount() / $orderLine->getQuantity())
                                        * $qtyDifference;
                                    $discountAmount = ($orderLine->getDiscountAmount() / $orderLine->getQuantity())
                                        * $qtyDifference;
                                    $lineNumber     = ((int)$orderLine->getLineNo()
                                        + (int)$order->getEditIncrement());
                                    $itemId         = $orderLine->getNumber();

                                    $coEditQtyUpdateLine = $this->orderHelper->createInstance(
                                        COEditLine::class
                                    );
                                    $coEditQtyUpdateLine->addData([
                                        COEditLine::LINE_NO             => $lineNumber,
                                        COEditLine::LINE_TYPE           => $orderLine->getLineType(),
                                        COEditLine::STATUS              => "",
                                        COEditLine::NUMBER              => $itemId,
                                        COEditLine::VARIANT_CODE        => $orderLine->getVariantCode(),
                                        COEditLine::UNITOF_MEASURE_CODE => $orderLine->getUomid(),
                                        COEditLine::NET_PRICE           => $netPrice,
                                        COEditLine::PRICE               => $price,
                                        COEditLine::QUANTITY            => $qtyDifference,
                                        COEditLine::DISCOUNT_AMOUNT     => $discountAmount,
                                        COEditLine::DISCOUNT_PERCENT    => $orderLine->getDiscountPercent(),
                                        COEditLine::NET_AMOUNT          => $netAmount,
                                        COEditLine::VAT_AMOUNT          => $taxAmount,
                                        COEditLine::AMOUNT              => $amount,
                                        COEditLine::STORE_NO            => $createdAtStore,
                                        COEditLine::EXTERNAL_ID         => $order->getIncrementId(),
                                        COEditLine::DOCUMENT_ID         => $documentId,
                                        COEditLine::RETAIL_IMAGE_ID     => "NEW_COLINE_INDICATOR" //New line indicator
                                    ]);

                                    $orderEditQtyUpdateCoLines[] = $coEditQtyUpdateLine;
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

        return $orderEditQtyUpdateCoLines;
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
        $orderLines = $customerOrder->getLscMemberSalesDocLine();
        foreach ($orderLinesArray as $line) {
            foreach ($orderLines as $orderLine) {
                if ($orderLine->getNumber() == $line->getNumber()
                    && $orderLine->getVariantCode() == $line->getVariantCode()
                    && $orderLine->getUnitOfMeasure() == $line->getUnitOfMeasureCode()) {
                    $line->setLineNo($orderLine->getLineNo());
                    $line->setQuantity($orderLine->getQuantity());
                    $line->setNetPrice($orderLine->getNetPrice());
                    $line->setPrice($orderLine->getPrice());
                    $line->setDiscountPercent($orderLine->getDiscount());
                    $line->setDiscountAmount($orderLine->getDiscountAmount());
                    $line->setNetAmount($orderLine->getNetAmount());
                    $line->setVatAmount($orderLine->getVatAmount());
                    $line->setAmount($orderLine->getAmount());
                }
            }
        }
    }
}
