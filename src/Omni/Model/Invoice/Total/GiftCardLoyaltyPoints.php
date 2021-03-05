<?php

namespace Ls\Omni\Model\Invoice\Total;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Class for handling gift card and loyalty points invoice
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
     * Calculation for loyalty points and gift card amount in invoice.
     * @param Invoice $invoice
     * @return $this|AbstractTotal
     * @throws NoSuchEntityException
     */
    public function collect(Invoice $invoice)
    {
        $pointsSpent = $invoice->getOrder()->getLsPointsSpent();
        $giftCardAmount = $invoice->getOrder()->getLsGiftCardAmountUsed();
        if ($pointsSpent > 0 || $giftCardAmount > 0) {
            $totalItemsQuantities = 0;
            $totalItemsInvoice = 0;
            $totalPointsAmount = 0;

            $invoice->setLsPointsSpent(0);
            $invoice->setLsGiftCardAmountUsed(0);
            $invoice->setLsGiftCardNo(null);

            $pointsEarn = $invoice->getOrder()->getLsPointsEarn();
            $invoice->setLsPointsEarn($pointsEarn);

            /** @var $item \Magento\Sales\Model\Order\Invoice\Item */
            foreach ($invoice->getOrder()->getAllVisibleItems() as $item) {
                $totalItemsQuantities = $totalItemsQuantities + $item->getQtyOrdered();
            }

            foreach ($invoice->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->getData('product_type') == 'simple') {
                    $totalItemsInvoice += $item->getQty() - $orderItem->getQtyInvoiced();
                }
            }

            $pointRate = ($this->loyaltyHelper->getPointRate()) ? $this->loyaltyHelper->getPointRate() : 0;
            $totalPointsAmount = $pointsSpent * $pointRate;
            $totalPointsAmount = ($totalPointsAmount / $totalItemsQuantities) * $totalItemsInvoice;
            $pointsSpent = ($pointsSpent / $totalItemsQuantities) * $totalItemsInvoice;

            $giftCardAmount = ($giftCardAmount / $totalItemsQuantities) * $totalItemsInvoice;

            $invoice->setLsPointsSpent($pointsSpent);
            $invoice->setLsGiftCardAmountUsed($giftCardAmount);

            $giftCardNo = $invoice->getOrder()->getLsGiftCardNo();
            $invoice->setLsGiftCardNo($giftCardNo);

            $grandTotalAmount = $invoice->getGrandTotal() - $totalPointsAmount - $giftCardAmount;
            $baseGrandTotalAmount = $invoice->getBaseGrandTotal() - $totalPointsAmount - $giftCardAmount;
            $invoice->setGrandTotal($grandTotalAmount);
            $invoice->setBaseGrandTotal($baseGrandTotalAmount);
        }

        return $this;
    }
}
