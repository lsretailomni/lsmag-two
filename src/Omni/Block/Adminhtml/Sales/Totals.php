<?php

namespace Ls\Omni\Block\Adminhtml\Sales;

use Ls\Omni\Helper\LoyaltyHelper;

/**
 * Class Totals
 * @package Ls\Omni\Block\Adminhtml\Sales
 */
class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Ls\Omni\Helper\OrderHelper
     */
    public $orderHelper;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    public $currency;

    /**
     * @var Ls\Omni\Helper\LoyaltyHelper
     */
    public $loyaltyHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Ls\Omni\Helper\OrderHelper $orderHelper,
        LoyaltyHelper $loyaltyHelper,
        \Magento\Directory\Model\Currency $currency,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->orderHelper = $orderHelper;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->currency = $currency;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
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

    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }

    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
    }

    /**
     *
     *
     * @return $this
     */
    public function initTotals()
    {
         $this->getParentBlock();
         $order=$this->getOrder();
         $order->setIncrementId($order->getDocumentId());
         $this->setOrder($order);
         $order2=$this->getOrder();
         $this->getInvoice();
         $this->getCreditmemo();
         $this->getSource();
        if ($this->getSource()->getLsPointsSpent()>0) {
            $loyaltyAmount = $this->getSource()->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            // @codingStandardsIgnoreLine
            $loyaltyPoints = new \Magento\Framework\DataObject(
                [
                    'code' => 'ls_points_spent',
                    'value' => -$loyaltyAmount,
                    'label' => __('Loyalty Points Redeemed'),
                ]
            );
            $this->getParentBlock()->addTotalBefore($loyaltyPoints, 'discount');
        }
        if ($this->getSource()->getLsGiftCardAmountUsed()>0) {
            // @codingStandardsIgnoreLine
            $giftCardAmount = new \Magento\Framework\DataObject(
                [
                    'code' => 'ls_gift_card_amount_used',
                    'value' => -$this->getSource()->getLsGiftCardAmountUsed(),
                    'label' => __('Gift Card Redeemed ') . '(' . $this->getSource()->getLsGiftCardNo() . ')',
                ]
            );
            $this->getParentBlock()->addTotalBefore($giftCardAmount, 'discount');
        }

        return $this;
    }
}