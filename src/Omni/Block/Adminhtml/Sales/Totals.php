<?php

namespace Ls\Omni\Block\Adminhtml\Sales;

use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;

/**
 * Class Totals
 * @package Ls\Omni\Block\Adminhtml\Sales
 */
class Totals extends Template
{
    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var Currency
     */
    public $currency;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * Totals constructor.
     * @param Context $context
     * @param OrderHelper $orderHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param Currency $currency
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderHelper $orderHelper,
        LoyaltyHelper $loyaltyHelper,
        Currency $currency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderHelper   = $orderHelper;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->currency      = $currency;
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
     * @throws NoSuchEntityException
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

        if ($this->getSource()->getLsGiftCardAmountUsed() > 0) {
            // @codingStandardsIgnoreLine
            $giftCardAmount = new DataObject(
                [
                    'code'  => 'ls_gift_card_amount_used',
                    'value' => -$this->getSource()->getLsGiftCardAmountUsed(),
                    'label' => __('Gift Card Redeemed ') . '(' . $this->getSource()->getLsGiftCardNo() . ')',
                ]
            );
            $this->getParentBlock()->addTotalBefore($giftCardAmount, 'discount');
        }

        if ($this->getSource()->getLsPointsSpent() > 0) {
            $loyaltyAmount = $this->getSource()->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            // @codingStandardsIgnoreLine
            $loyaltyPoints = new DataObject(
                [
                    'code'  => 'ls_points_spent',
                    'value' => -$loyaltyAmount,
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
                    'label' => __('Discount'),
                ]
            );
            $this->getParentBlock()->addTotalBefore($lsDiscounts, 'discount');
        }

        return $this;
    }
}
