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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

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
     * @var LSR
     */
    private $lsr;

    /**
     * @var PriceHelper
     */
    public $priceHelper;

    /**
     * DataHelper constructor.
     * @param LoyaltyHelper $loyaltyHelper
     * @param OrderHelper $orderHelper
     * @param Helper $helper
     * @param LSR $lsr
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper,
        OrderHelper $orderHelper,
        Helper $helper,
        PriceHelper $priceHelper,
        LSR $lsr
    ) {
        $this->loyaltyHelper = $loyaltyHelper;
        $this->orderHelper   = $orderHelper;
        $this->helper        = $helper;
        $this->priceHelper   = $priceHelper;
        $this->lsr           = $lsr;
    }

    /**
     * get member contact related data
     * @param $context
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
                    $scheme = $result->getAccount()->getScheme();
                    if (!empty($scheme)) {
                        $schemeArray                  = [];
                        $schemeArray['club_name']     = $scheme->getClub()->getName();
                        $schemeArray['loyalty_level'] = $scheme->getDescription();
                        $schemeArray['point_balance'] = $result->getAccount()->getPointBalance();
                        $nextSchemeLevel              = $scheme->getNextScheme();
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
     * @param $context
     * @param $maxNumberOfEntries
     * @return array|ArrayOfSalesEntry|SalesEntriesGetByCardIdResponse|ResponseInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSalesEntries($context, $maxNumberOfEntries)
    {
        $salesEntriesArray = [];
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
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
     * @param $context
     * @param $documentId
     * @param $type
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
            $salesEntry           = $this->orderHelper->getOrderDetailsAgainstId($documentId, $type);
            $salesEntriesArray [] = $this->getSaleEntry($salesEntry);
        }

        return $salesEntriesArray;
    }

    /**
     * Get Sales entry info
     * @param SalesEntry $salesEntry
     * @return array
     */
    public function getSaleEntry(SalesEntry $salesEntry): array
    {
        return [
            'id'                      => $salesEntry->getId(),
            'click_and_collect_order' => $salesEntry->getClickAndCollectOrder(),
            'document_reg_time'       => $salesEntry->getDocumentRegTime(),
            'external_id'             => $salesEntry->getExternalId(),
            'payment_status'          => $salesEntry->getPaymentStatus(),
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
            'total_amount'            => $this->formatValue($salesEntry->getTotalAmount()),
            'total_net_amount'        => $this->formatValue($salesEntry->getTotalNetAmount()),
            'total_discount'          => $this->formatValue($salesEntry->getTotalDiscount()),
            'contact_address'         => $this->getAddress($salesEntry->getContactAddress()),
            'ship_to_address'         => $this->getAddress($salesEntry->getShipToAddress()),
            'payments'                => $this->getPayments($salesEntry->getPayments()),
            'items'                   => $this->getItems($salesEntry->getLines())
        ];
    }

    /**
     * Get address info
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
     * @param ArrayOfSalesEntryPayment $payments
     * @return array
     */
    public function getPayments(ArrayOfSalesEntryPayment $payments): array
    {
        $paymentsArray = [];
        foreach ($payments->getSalesEntryPayment() as $payment) {
            $paymentsArray[] = [
                'amount'          => $this->formatValue($payment->getAmount()),
                'card_no'         => $payment->getCardNo(),
                'currency_code'   => $payment->getCurrencyCode(),
                'currency_factor' => $this->formatValue($payment->getCurrencyFactor()),
                'line_number'     => $payment->getLineNumber(),
                'tender_type'     => $payment->getTenderType(),
            ];
        }

        return $paymentsArray;
    }

    /**
     * Get items array
     * @param ArrayOfSalesEntryLine $items
     * @return array
     */
    public function getItems(ArrayOfSalesEntryLine $items): array
    {
        $itemsArray = [];
        foreach ($items->getSalesEntryLine() as $item) {
            $itemsArray[] = [
                'amount'                 => $this->formatValue($item->getAmount()),
                'click_and_collect_line' => $item->getClickAndCollectLine(),
                'discount_amount'        => $this->formatValue($item->getDiscountAmount()),
                'discount_percent'       => $this->formatValue($item->getDiscountPercent()),
                'item_description'       => $item->getItemDescription(),
                'item_id'                => $item->getItemId(),
                'item_image_id'          => $item->getItemImageId(),
                'line_number'            => $item->getLineNumber(),
                'line_type'              => $item->getLineType(),
                'net_amount'             => $this->formatValue($item->getNetAmount()),
                'net_price'              => $this->formatValue($item->getNetPrice()),
                'parent_line'            => $item->getParentLine(),
                'price'                  => $this->formatValue($item->getPrice()),
                'quantity'               => $this->formatValue($item->getQuantity()),
                'store_id'               => $item->getStoreId(),
                'tax_amount'             => $this->formatValue($item->getTaxAmount()),
                'uom_id'                 => $item->getUomId(),
                'variant_description'    => $item->getVariantDescription(),
                'variant_id'             => $item->getVariantId()
            ];
        }

        return $itemsArray;
    }

    /**
     * Format value to two decimal places
     * @param $value
     * @return float|string
     */
    public function formatValue($value)
    {
        return $this->priceHelper->currency($value, false, false);
    }
}
