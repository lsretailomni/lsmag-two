<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Adminhtml\Sales;

use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;

class Totals extends Template
{
    /**
     * @param Context $context
     * @param OrderHelper $orderHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param Currency $currency
     * @param array $data
     */
    public function __construct(
        Context $context,
        public OrderHelper $orderHelper,
        public LoyaltyHelper $loyaltyHelper,
        public Currency $currency,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * @return mixed
     */
    public function setOrder($order)
    {
        return $this->getParentBlock()->setOrder($order);
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->currency->getCurrencySymbol();
    }

    /**
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }

    /**
     * @return mixed
     */
    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
    }

    /**
     * Initiate all totals
     *
     * @return $this
     * @throws NoSuchEntityException|LocalizedException
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $order = $this->getOrder();
        $order->setIncrementId($order->getDocumentId());
        $this->setOrder($order);
        $this->getInvoice();
        $this->getCreditmemo();
        $this->getSource();
        $this->loyaltyHelper->getLsr()->setStoreId($order->getStoreId());

        $allEntries = json_decode((string)$this->getSource()->getLsPosDataEntries(), true) ?? [];

        $gcEntries = array_values(array_filter($allEntries, fn($e) => strtoupper($e['entry_type'] ?? '') === 'GIFTCARDNO'));
        $gcTotal   = (float)array_sum(array_column($gcEntries, 'amount'));
        if ($gcTotal > 0) {
            $gcCount = count($gcEntries);
            $gcTitle = $gcCount === 1
                ? __('%1 - %2 Redeemed', $gcEntries[0]['entry_type'] ?? 'Gift Card', $gcEntries[0]['entry_no'] ?? '')
                : __('Gift Cards Redeemed (%1)', $gcCount);
            // @codingStandardsIgnoreLine
            $giftCardAmount = new DataObject(
                [
                    'code'       => 'ls_gift_card_amount_used',
                    'value'      => -$gcTotal,
                    'base_value' => -$this->loyaltyHelper->itemHelper->convertToBaseCurrency($gcTotal),
                    'label'      => $gcTitle,
                ]
            );
            $this->getParentBlock()->addTotalBefore($giftCardAmount, 'discount');
        }

        $voucherEntries = array_values(array_filter($allEntries, fn($e) => strtoupper($e['entry_type'] ?? '') !== 'GIFTCARDNO'));
        $voucherTotal   = (float)array_sum(array_column($voucherEntries, 'amount'));
        if ($voucherTotal > 0) {
            $vCount = count($voucherEntries);
            $vTitle = $vCount === 1
                ? __('%1 - %2 Redeemed', $voucherEntries[0]['entry_type'] ?? 'Voucher', $voucherEntries[0]['entry_no'] ?? '')
                : __('Vouchers Redeemed (%1)', $vCount);
            // @codingStandardsIgnoreLine
            $voucherAmount = new DataObject(
                [
                    'code'       => 'ls_entry_amount',
                    'value'      => -$voucherTotal,
                    'base_value' => -$this->loyaltyHelper->itemHelper->convertToBaseCurrency($voucherTotal),
                    'label'      => $vTitle,
                ]
            );
            $this->getParentBlock()->addTotalBefore($voucherAmount, 'discount');
        }

        if ($this->getSource()->getLsPointsSpent() > 0) {
            $loyaltyAmount = $this->loyaltyHelper->getLsPointsDiscount($this->getSource()->getLsPointsSpent());
            // @codingStandardsIgnoreLine
            $loyaltyPoints = new DataObject(
                [
                    'code'  => 'ls_points_spent',
                    'value' => -$loyaltyAmount,
                    'base_value' => -$this->loyaltyHelper->itemHelper->convertToBaseCurrency($loyaltyAmount),
                    'label' => __('Loyalty Points Redeemed'),
                ]
            );
            $this->getParentBlock()->addTotalBefore($loyaltyPoints, 'discount');
        }

        if ($this->getSource()->getLsDiscountAmount() > 0) {
            $lsDiscountAmount = $this->getSource()->getLsDiscountAmount();
            // @codingStandardsIgnoreLine
            $lsDiscounts = new DataObject(
                [
                    'code'  => 'ls_discount_amount',
                    'value' => -$lsDiscountAmount,
                    'base_value' => -$this->loyaltyHelper->itemHelper->convertToBaseCurrency($lsDiscountAmount),
                    'label' => __('Discount'),
                ]
            );
            $this->getParentBlock()->addTotalBefore($lsDiscounts, 'discount');
        }

        return $this;
    }
}
