<?php

namespace Ls\Omni\Model\Creditmemo\Total;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Class for handling gift card and loyalty points in credit memo
 */
class GiftCardLoyaltyPoints extends AbstractTotal
{

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * GiftCardLoyaltyPoints constructor.
     * @param LoyaltyHelper $loyaltyHelper
     * @param array $data
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper,
        array $data = []
    ) {
        $this->loyaltyHelper = $loyaltyHelper;
        parent::__construct(
            $data
        );
    }

    /**
     * Calculation for loyalty points and gift card amount in credit memo.
     * @param Creditmemo $creditmemo
     * @return $this|GiftCardLoyaltyPoints
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function collect(Creditmemo $creditmemo)
    {
        $pointsSpent = $creditmemo->getOrder()->getLsPointsSpent();
        $giftCardAmount = $creditmemo->getOrder()->getLsGiftCardAmountUsed();
        if ($pointsSpent > 0 || $giftCardAmount > 0) {
            $totalItemsQuantities = 0;
            $totalItemsInvoice = 0;
            $totalPointsAmount = 0;

            $creditmemo->setLsPointsSpent(0);
            $creditmemo->setLsGiftCardAmountUsed(0);
            $creditmemo->setLsGiftCardNo(null);

            $pointsEarn = $creditmemo->getOrder()->getLsPointsEarn();
            $creditmemo->setLsPointsEarn($pointsEarn);

            /** @var $item \Magento\Sales\Model\Order\Invoice\Item */
            foreach ($creditmemo->getOrder()->getAllVisibleItems() as $item) {
                $totalItemsQuantities = $totalItemsQuantities + $item->getQtyOrdered();
            }

            foreach ($creditmemo->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->getData('product_type') == 'simple') {
                    $totalItemsInvoice += $item->getQty() - $orderItem->getQtyRefunded();
                }
            }

            $pointRate = ($this->loyaltyHelper->getPointRate()) ? $this->loyaltyHelper->getPointRate() : 0;
            $totalPointsAmount = $pointsSpent * $pointRate;
            $totalPointsAmount = ($totalPointsAmount / $totalItemsQuantities) * $totalItemsInvoice;
            $pointsSpent = ($pointsSpent / $totalItemsQuantities) * $totalItemsInvoice;

            $giftCardAmount = ($giftCardAmount / $totalItemsQuantities) * $totalItemsInvoice;

            $creditmemo->setLsPointsSpent($pointsSpent);
            $creditmemo->setLsGiftCardAmountUsed($giftCardAmount);

            $giftCardNo = $creditmemo->getOrder()->getLsGiftCardNo();
            $creditmemo->setLsGiftCardNo($giftCardNo);

            $grandTotalAmount = $creditmemo->getGrandTotal() - $totalPointsAmount - $giftCardAmount;
            $baseGrandTotalAmount = $creditmemo->getBaseGrandTotal() - $totalPointsAmount - $giftCardAmount;
            $creditmemo->setGrandTotal($grandTotalAmount);
            $creditmemo->setBaseGrandTotal($baseGrandTotalAmount);
        }

        return $this;
    }
}
