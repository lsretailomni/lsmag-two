<?php
declare(strict_types=1);

namespace Ls\CustomerGraphQl\Helper;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use Ls\Omni\Client\Ecommerce\Entity\GetSalesInfoByOrderId_GetSalesInfoByOrderId;
use Ls\Omni\Client\Ecommerce\Entity\LSCMemberSalesBuffer;
use Ls\Omni\Client\Ecommerce\Operation\GetSelectedSalesDoc_GetSelectedSalesDoc;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\OmniGraphQl\Helper\DataHelper as Helper;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\Data;
use Magento\Directory\Model\Currency;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Data Helper for getting customer related information
 */
class DataHelper
{
    /**
     * @param LoyaltyHelper $loyaltyHelper
     * @param OrderHelper $orderHelper
     * @param Helper $helper
     * @param Data $data
     * @param Currency $currencyHelper
     * @param LSR $lsr
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        public LoyaltyHelper $loyaltyHelper,
        public OrderHelper $orderHelper,
        public Helper $helper,
        public Data $data,
        public Currency $currencyHelper,
        public LSR $lsr,
        public ItemHelper $itemHelper
    ) {
    }

    /**
     * Get member contact related data
     *
     * @param mixed $context
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getMembersInfo($context)
    {
        $customerAccount = [];
        $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsiteId();
        $userId = $context->getUserId();
        $this->helper->setCustomerValuesInSession($userId, $websiteId);
        $customer = $this->helper->getCustomerSession()->getCustomer();
        $cardId = $customer->getData('lsr_cardid');

        if (!empty($cardId)) {
            $customerAccount['card_id'] = $cardId;
            $customerAccount['contact_id'] = $customer->getData('lsr_id');
            $customerAccount['username'] = $customer->getData('lsr_username');

            if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                $result = $this->loyaltyHelper->getMemberInfo();

                if ($result) {
                    $customerAccount['account_id'] = $result->getLscMemberAccount()->getNo();

                    if (!empty($result->getLscMemberClub())) {
                        $schemeArray = [];
                        $schemeArray['club_name'] = $result->getLscMemberClub()->getDescription();
                        $schemeArray['loyalty_level'] = $result->getLscMemberScheme()->getDescription();
                        $schemeArray['point_balance'] = $this->loyaltyHelper->getLoyaltyPointsAvailableToCustomer();

                        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) &&
                            $this->lsr->getStoreConfig(
                                LSR::SC_LOYALTY_POINTS_EXPIRY_CHECK,
                                $this->lsr->getCurrentStoreId()
                            )) {
                            $totalExpiryPoints = $this->loyaltyHelper->getPointBalanceExpirySum();

                            if ($totalExpiryPoints) {
                                $schemeArray['points_expiry'] = $totalExpiryPoints;

                                $expiryInterval = $this->lsr->getStoreConfig(
                                    LSR::SC_LOYALTY_POINTS_EXPIRY_NOTIFICATION_INTERVAL,
                                    $this->lsr->getCurrentStoreId()
                                );
                                $schemeArray['points_expiry_interval'] = $expiryInterval;
                            }
                        }
                        $nextSchemeLevel = $this->loyaltyHelper->getNextScheme(
                            (string)$result->getLscMemberScheme()->getClubCode(),
                            (string)$result->getLscMemberScheme()->getUpdateSequence()
                        );

                        if (!empty($nextSchemeLevel)) {
                            $schemeArray['next_level']['club_name'] = $result->getLscMemberClub()->getDescription();
                            $schemeArray['next_level']['loyalty_level'] = $nextSchemeLevel['Description'];
                            $schemeArray['next_level']['benefits'] =
                                $result->getLscMemberScheme()->getNextSchemeBenefits();
                            $schemeArray['next_level']['points_needed'] = $nextSchemeLevel['Min. Point for Upgrade'];
                        }
                        $customerAccount['scheme'] = $schemeArray;
                    }
                }
            }
        }

        return $customerAccount;
    }

    /**
     * Get sales entries information
     *
     * @param $context
     * @param $maxNumberOfEntries
     * @return array
     * @throws GuzzleException
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSalesEntries($context, $maxNumberOfEntries)
    {
        $salesEntriesArray = [];

        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getCustomerIntegrationOnFrontend()
        )) {
            $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsiteId();
            $userId = $context->getUserId();
            $this->helper->setCustomerValuesInSession($userId, $websiteId);

            if ($salesEntries = $this->orderHelper->getCurrentCustomerOrderHistory($maxNumberOfEntries)) {
                $salesEntries = $this->orderHelper->processOrderData($salesEntries);
            }

            foreach ($salesEntries as $salesEntry) {
                $orderId = $salesEntry->getData('IdType') == 'Order' && !empty($salesEntry->getCustomerDocumentId()) ?
                    $salesEntry->getCustomerDocumentId() : $salesEntry->getDocumentId();
                $orderType = $salesEntry->getData('IdType') == 'Order' && !empty($salesEntry->getDocumentId()) ?
                    DocumentIdType::ORDER : $salesEntry->getData('IdType');
                $salesEntryDetails = $this->orderHelper->fetchOrder(
                    $orderId,
                    $orderType
                );

                if ($salesEntryDetails) {
                    $salesEntriesArray [] = $this->getSaleEntry($salesEntry, $salesEntryDetails);
                }
            }
        }

        return $salesEntriesArray;
    }

    /**
     * Get sales entry information by document id
     *
     * @param mixed $context
     * @param string $documentId
     * @param string $type
     * @return array
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getSalesEntryByDocumentId($context, $documentId, $type)
    {
        $salesEntriesArray = [];
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsiteId();
            $userId = $context->getUserId();
            $this->helper->setCustomerValuesInSession($userId, $websiteId);
            $salesEntryDetails = $this->orderHelper->getOrderDetailsAgainstId($documentId, $type);

            if (!empty($salesEntryDetails)) {
                $salesEntry = $salesEntryDetails->getLscMemberSalesBuffer();
                $customerDocId = $salesEntry->getCustomerDocumentId();
                $magOrder = $this->orderHelper->getMagentoOrderGivenDocumentId($customerDocId);
                $salesEntriesArray [] = $this->getSaleEntry(
                    $salesEntry,
                    $salesEntryDetails,
                    $magOrder
                );
            }
        }

        return $salesEntriesArray;
    }

    /**
     * Get Sales entry info
     *
     * @param LSCMemberSalesBuffer $salesEntry
     * @param GetSelectedSalesDoc_GetSelectedSalesDoc|GetSalesInfoByOrderId_GetSalesInfoByOrderId $salesEntryDetails
     * @param mixed $magOrder
     * @return array
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function getSaleEntry(
        LSCMemberSalesBuffer $salesEntry,
        $salesEntryDetails,
        $magOrder = null
    ): array {
        $externalId = '';
        $orderCurrencyCode = '';
        $documentId = !empty($salesEntry->getCustomerDocumentId()) ?
            $salesEntry->getCustomerDocumentId() :
            (!empty($salesEntry->getDocumentId()) ? $salesEntry->getDocumentId() : "");

        if (!$magOrder && !empty($documentId)) {
            $magOrder = $this->orderHelper->getOrderByDocumentId($documentId);
        }

        if (!empty($magOrder)) {
            $externalId = $magOrder->getIncrementId();
            $orderCurrencyCode = $magOrder->getOrderCurrencyCode();
        }
        $orderCurrencyCode = ($salesEntry->getStoreCurrencyCode()) ?: $orderCurrencyCode;
        $orderId = !empty($salesEntry->getCustomerDocumentId()) ?
            $salesEntry->getCustomerDocumentId() :
            (!empty($salesEntry->getDocumentId()) ? $salesEntry->getDocumentId() : "");

        $itemLines = $paymentLines = $orderTransactions = [];

        if (!empty($salesEntryDetails->getLscMemberSalesDocLine())) {
            $orderTransactions = is_array($salesEntryDetails->getLscMemberSalesDocLine()) ?
                $salesEntryDetails->getLscMemberSalesDocLine() :
                [$salesEntryDetails->getLscMemberSalesDocLine()];
        }

        foreach ($orderTransactions as $line) {
            if ($line->getDocumentId() !== $orderId) {
                continue;
            }

            if ($line->getEntryType() == 1) {
                $paymentLines[] = $line;
            } else {
                $itemLines[] = $line;
            }
        }

        $isClickNCollectOrder = $this->isClickAndCollectOrder($salesEntryDetails);
        return [
            'id' => $salesEntry->getDocumentId(),
            'click_and_collect_order' => $isClickNCollectOrder,
            'document_reg_time' => $salesEntry->getCreateDatetime(),
            'document_id' => $salesEntry->getDocumentId(),
            'external_id' => ($salesEntry->getExternalId()) ?: $externalId,
            'id_type' => $salesEntry->getData('IdType'),
            'line_item_count' => $salesEntry->getNumberOfLines(),
            'points_rewarded' => $salesEntry->getPointsRewarded(),
            'points_used' => $salesEntry->getPointsUsedInOrder(),
            'posted' => $salesEntry->getPosted(),
            'ship_to_name' => $salesEntry->getShipToName(),
            'ship_to_email' => $salesEntry->getShipToEmail(),
            'status' => $salesEntry->getStatus(),
            'store_id' => $salesEntry->getStoreId(),
            'store_name' => $salesEntry->getStoreName(),
            'store_currency' => ($salesEntry->getStoreCurrency()) ?: $orderCurrencyCode,
            'total_amount' => $this->formatValue($salesEntry->getGrossAmount()),
            'total_net_amount' => $this->formatValue($salesEntry->getNetAmount()),
            'total_tax_amount' => $this->formatValue(
                $salesEntry->getGrossAmount() - $salesEntry->getNetAmount()
            ),
            'total_discount' => $this->formatValue($salesEntry->getDiscountAmount()),
            'contact_address' => $this->getAddress($salesEntry, true),
            'ship_to_address' => $this->getAddress($salesEntry),
            'payments' => $this->getPayments($paymentLines, $orderCurrencyCode),
            'items' => $this->getItems($itemLines, $magOrder)
        ];
    }

    /**
     * Check to see if current order is click and collect
     *
     * @return bool
     */
    public function isClickAndCollectOrder($order)
    {
        $isCc = false;

        $lines = !is_array($order->getLscMemberSalesDocLine()) ?
            [$order->getLscMemberSalesDocLine()] :
            $order->getLscMemberSalesDocLine();

        foreach ($lines as $line) {
            if ($line->getClickAndCollectLine()) {
                $isCc = true;
                break;
            }
        }

        return $isCc;
    }

    /**
     * Get address info
     *
     * @param LSCMemberSalesBuffer $order
     * @param bool $isBillingAddress
     * @return array
     */
    public function getAddress($order, bool $isBillingAddress = false): array
    {
        if ($isBillingAddress) {
            if (!empty($order->getAddress()) && !empty($order->getCountryRegionCode())) {
                return [
                    'address1' => $order->getAddress(),
                    'address2' => $order->getAddress2(),
                    'cell_phone_number' => $order->getPhoneNo(),
                    'city' => $order->getCity(),
                    'country' => $this->orderHelper->getCountryName($order->getCountryRegionCode()),
                    'house_no' => '',
                    'post_code' => $order->getPostCode(),
                    'state_province_region' => $order->getCounty(),
                    'type' => 'Residential',
                ];
            }
        } else {
            if (!empty($order->getShipToName()) && !empty($order->getCountryRegionCode())) {

                return [
                    'address1' => $order->getShipToAddress(),
                    'address2' => $order->getShipToAddress2(),
                    'cell_phone_number' => $order->getShipToPhoneNo(),
                    'city' => $order->getShipToCity(),
                    'country' => $this->orderHelper->getCountryName($order->getShipToCountryRegionCode()),
                    'house_no' => '',
                    'post_code' => $order->getShipToPostCode(),
                    'state_province_region' => $order->getShipToCounty(),
                    'type' => 'Residential',
                ];

            }
        }

        return [];
    }

    /**
     * Get payments array
     *
     * @param array $payments
     * @param string $orderCurrencyCode
     * @return array
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getPayments($payments, $orderCurrencyCode): array
    {
        $paymentsArray     = [];
        $tenderTypeMapping = $this->data->getTenderTypesPaymentMapping();
        foreach ($payments as $payment) {
            if ($payment->getEntryType() == 1) {
                $tenderType = $payment->getNumber();
                if (array_key_exists($tenderType, $tenderTypeMapping)) {
                    $tenderType = $tenderTypeMapping[$tenderType];
                }

                $currency = $payment->getCurrencyCode();
                $amount = $payment->getAmount();

                if ($payment->getCurrencyCode() == 'LOY') {
                    $currency = $orderCurrencyCode;
                    $amount = $amount * $payment->getCurrencyFactor();
                }

                $paymentsArray[] = [
                    'amount' => $amount,
                    'card_no' => $payment->getCardNo(),
                    'currency_code' => $currency,
                    'currency_factor' => $payment->getCurrencyFactor(),
                    'line_number' => $payment->getLineNumber(),
                    'tender_type' => $tenderType,
                ];
            }
        }

        return $paymentsArray;
    }

    /**
     * Get items array
     *
     * @param array $items
     * @param $magOrder
     * @return array
     */
    public function getItems($items, $magOrder = null): array
    {
        $itemsArray = [];
        foreach ($items as $item) {
            $itemsArray[] = [
                'amount'                 => $item->getAmount(),
                'click_and_collect_line' => $item->getClickAndCollectLine(),
                'discount_amount'        => $item->getDiscountAmount(),
                'discount_percent'       => $item->getDiscount(),
                'item_description'       => $item->getDescription(),
                'item_id'                => $item->getNumber(),
                'item_image_id'          => $item->getImageId(),
                'line_number'            => $item->getLineNo(),
                'line_type'              => 'Item',
                'net_amount'             => $item->getNetAmount(),
                'net_price'              => $item->getNetPrice(),
                'parent_line'            => $item->getParentLine(),
                'price'                  => $item->getPrice(),
                'quantity'               => $item->getQuantity(),
                'store_id'               => $item->getStoreNo(),
                'tax_amount'             => $item->getVatAmount(),
                'uom_id'                 => $item->getUnitOfMeasure(),
                'variant_description'    => $item->getVariantDescription(),
                'variant_id'             => $item->getVariantCode()
            ];
        }

        return $itemsArray;
    }

    /**
     * Format value to two decimal places
     *
     * @param float $value
     * @return string
     */
    public function formatValue($value)
    {
        return $this->currencyHelper->format($value, ['display' => 1], false);
    }
}
