<?php

namespace Ls\CustomerGraphQl\Helper;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Address;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntryLine;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntryPayment;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntriesGetByCardIdResponse;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\ResponseInterface;
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
     * @var LoyaltyHelper
     */
    private $loyaltyHelper;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var Currency
     */
    public $currencyHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

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
        LoyaltyHelper $loyaltyHelper,
        OrderHelper $orderHelper,
        Helper $helper,
        Data $data,
        Currency $currencyHelper,
        LSR $lsr,
        ItemHelper $itemHelper
    ) {
        $this->loyaltyHelper  = $loyaltyHelper;
        $this->orderHelper    = $orderHelper;
        $this->helper         = $helper;
        $this->data           = $data;
        $this->currencyHelper = $currencyHelper;
        $this->lsr            = $lsr;
        $this->itemHelper     = $itemHelper;
    }

    /**
     * Get member contact related data
     *
     * @param mixed $context
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getMembersInfo($context)
    {
        $customerAccount = [];
        $websiteId       = (int)$context->getExtensionAttributes()->getStore()->getWebsiteId();
        $userId          = $context->getUserId();
        $this->helper->setCustomerValuesInSession($userId, $websiteId);
        $customer = $this->helper->getCustomerSession()->getCustomer();
        $cardId   = $customer->getData('lsr_cardid');
        if (!empty($cardId)) {
            $customerAccount ['card_id']   = $cardId;
            $customerAccount['contact_id'] = $customer->getData('lsr_id');
            $customerAccount['username']   = $customer->getData('lsr_username');
            if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                $result = $this->loyaltyHelper->getMemberInfo();
                if ($result) {
                    $customerAccount['account_id'] = $result->getAccount()->getId();
                    $scheme                        = $result->getAccount()->getScheme();
                    if (!empty($scheme)) {
                        $schemeArray                  = [];
                        $schemeArray['club_name']     = $scheme->getClub()->getName();
                        $schemeArray['loyalty_level'] = $scheme->getDescription();
                        $schemeArray['point_balance'] = $result->getAccount()->getPointBalance();
                        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) && $this->lsr->getStoreConfig(
                                LSR::SC_LOYALTY_POINTS_EXPIRY_CHECK,
                                $this->lsr->getCurrentStoreId()
                            )) {
                            $totalExpiryPoints = $this->loyaltyHelper->getPointBalanceExpirySum();
                            if ($totalExpiryPoints) {
                                $schemeArray['points_expiry'] = $totalExpiryPoints;

                                $expiryInterval                        = $this->lsr->getStoreConfig(
                                    LSR::SC_LOYALTY_POINTS_EXPIRY_NOTIFICATION_INTERVAL,
                                    $this->lsr->getCurrentStoreId()
                                );
                                $schemeArray['points_expiry_interval'] = $expiryInterval;
                            }
                        }
                        $nextSchemeLevel = $scheme->getNextScheme();
                        if (!empty($nextSchemeLevel)) {
                            $schemeArray['next_level']['club_name']     = $nextSchemeLevel->getClub()->getName();
                            $schemeArray['next_level']['loyalty_level'] = $nextSchemeLevel->getDescription();
                            $schemeArray['next_level']['benefits']      = $nextSchemeLevel->getPerks();
                            $schemeArray['next_level']['points_needed'] = $nextSchemeLevel->getPointsNeeded();
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
     * @param mixed $context
     * @param int|null $maxNumberOfEntries
     * @return array
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
            $userId    = $context->getUserId();
            $this->helper->setCustomerValuesInSession($userId, $websiteId);
            $salesEntries = $this->orderHelper->getCurrentCustomerOrderHistory($maxNumberOfEntries);
            foreach ($salesEntries as $salesEntry) {
                $salesEntriesArray [] = $this->getSaleEntry($salesEntry);
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
     * @return array|ArrayOfSalesEntry|SalesEntriesGetByCardIdResponse|ResponseInterface
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSalesEntryByDocumentId($context, $documentId, $type)
    {
        $salesEntriesArray = [];
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsiteId();
            $userId    = $context->getUserId();
            $this->helper->setCustomerValuesInSession($userId, $websiteId);
            $salesEntry = $this->orderHelper->getOrderDetailsAgainstId($documentId, $type);
            $magOrder   = $this->orderHelper->getMagentoOrderGivenDocumentId($documentId);
            if (!empty($salesEntry)) {
                $salesEntriesArray [] = $this->getSaleEntry($salesEntry, $magOrder);
            }
        }

        return $salesEntriesArray;
    }

    /**
     * Get Sales entry info
     *
     * @param SalesEntry $salesEntry
     * @param $magOrder
     * @return array
     * @throws NoSuchEntityException
     */
    public function getSaleEntry(SalesEntry $salesEntry, $magOrder = null): array
    {
        $externalId        = '';
        $orderCurrencyCode = '';
        if (!$magOrder) {
            $magOrder = $this->orderHelper->getOrderByDocumentId($salesEntry);
        }

        if (!empty($magOrder)) {
            $externalId        = $magOrder->getIncrementId();
            $orderCurrencyCode = $magOrder->getOrderCurrencyCode();
        }
        $orderCurrencyCode = ($salesEntry->getStoreCurrency()) ?: $orderCurrencyCode;
        return [
            'id'                      => $salesEntry->getId(),
            'click_and_collect_order' => $salesEntry->getClickAndCollectOrder(),
            'document_reg_time'       => $salesEntry->getDocumentRegTime(),
            'document_id'             => $salesEntry->getCustomerOrderNo(),
            'external_id'             => ($salesEntry->getExternalId()) ?: $externalId,
            'id_type'                 => $salesEntry->getIdType(),
            'line_item_count'         => $salesEntry->getLineItemCount(),
            'points_rewarded'         => $salesEntry->getPointsRewarded(),
            'points_used'             => $salesEntry->getPointsUsedInOrder(),
            'posted'                  => $salesEntry->getPosted(),
            'ship_to_name'            => $salesEntry->getShipToName(),
            'ship_to_email'           => $salesEntry->getShipToEmail(),
            'status'                  => $salesEntry->getStatus(),
            'store_id'                => $salesEntry->getStoreId(),
            'store_name'              => $salesEntry->getStoreName(),
            'store_currency'          => ($salesEntry->getStoreCurrency()) ?: $orderCurrencyCode,
            'total_amount'            => $this->formatValue($salesEntry->getTotalAmount()),
            'total_net_amount'        => $this->formatValue($salesEntry->getTotalNetAmount()),
            'total_tax_amount'        => $this->formatValue(
                $salesEntry->getTotalAmount() - $salesEntry->getTotalNetAmount()
            ),
            'total_discount'          => $this->formatValue($salesEntry->getTotalDiscount()),
            'contact_address'         => $this->getAddress($salesEntry->getContactAddress()),
            'ship_to_address'         => $this->getAddress($salesEntry->getShipToAddress()),
            'payments'                => $this->getPayments($salesEntry->getPayments(), $orderCurrencyCode),
            'items'                   => $this->getItems($salesEntry->getLines(), $magOrder)
        ];
    }

    /**
     * Get address info
     *
     * @param Address $address
     * @return array
     */
    public function getAddress(Address $address): array
    {
        return [
            'address1'              => $address->getAddress1(),
            'address2'              => $address->getAddress2(),
            'cell_phone_number'     => $address->getCellPhoneNumber(),
            'city'                  => $address->getCity(),
            'country'               => $address->getCountry(),
            'house_no'              => $address->getHouseNo(),
            'post_code'             => $address->getPostCode(),
            'state_province_region' => $address->getStateProvinceRegion(),
            'type'                  => $address->getType(),
        ];
    }

    /**
     * Get payments array
     *
     * @param ArrayOfSalesEntryPayment $payments
     * @param $orderCurrencyCode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPayments(ArrayOfSalesEntryPayment $payments, $orderCurrencyCode): array
    {
        $paymentsArray     = [];
        $tenderTypeMapping = $this->data->getTenderTypesPaymentMapping();
        foreach ($payments->getSalesEntryPayment() as $payment) {
            $tenderType = $payment->getTenderType();
            if (array_key_exists($tenderType, $tenderTypeMapping)) {
                $tenderType = $tenderTypeMapping[$tenderType];
            }

            $currency = $payment->getCurrencyCode();
            $amount   = $payment->getAmount();

            if ($payment->getCurrencyCode() == 'LOY') {
                $currency = $orderCurrencyCode;
                $amount   = $amount * $payment->getCurrencyFactor();
            }

            $paymentsArray[] = [
                'amount'          => $amount,
                'card_no'         => $payment->getCardNo(),
                'currency_code'   => $currency,
                'currency_factor' => $payment->getCurrencyFactor(),
                'line_number'     => $payment->getLineNumber(),
                'tender_type'     => $tenderType,
            ];
        }

        return $paymentsArray;
    }

    /**
     * Get items array
     *
     * @param ArrayOfSalesEntryLine $items
     * @param $magOrder
     * @return array
     */
    public function getItems(ArrayOfSalesEntryLine $items, $magOrder = null): array
    {
        $itemsArray = [];
        foreach ($items->getSalesEntryLine() as $item) {
            $itemsArray[] = [
                'amount'                 => $item->getAmount(),
                'click_and_collect_line' => $item->getClickAndCollectLine(),
                'discount_amount'        => $item->getDiscountAmount(),
                'discount_percent'       => $item->getDiscountPercent(),
                'item_description'       => $item->getItemDescription(),
                'item_id'                => $item->getItemId(),
                'item_image_id'          => $item->getItemImageId(),
                'line_number'            => $item->getLineNumber(),
                'line_type'              => $item->getLineType(),
                'net_amount'             => $item->getNetAmount(),
                'net_price'              => $item->getNetPrice(),
                'parent_line'            => $item->getParentLine(),
                'price'                  => $item->getPrice(),
                'quantity'               => $item->getQuantity(),
                'store_id'               => $item->getStoreId(),
                'tax_amount'             => $item->getTaxAmount(),
                'uom_id'                 => $item->getUomId(),
                'variant_description'    => $item->getVariantDescription(),
                'variant_id'             => $item->getVariantId()
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
