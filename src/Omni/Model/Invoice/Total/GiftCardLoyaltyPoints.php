<?php

namespace Ls\Omni\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;
use \Ls\Omni\Helper\LoyaltyHelper;

/**
 * Class GiftCardLoyaltyPoints
 * @package Ls\Omni\Model
 */
class GiftCardLoyaltyPoints extends AbstractTotal
{

    /**
     * @var \Ls\Omni\Helper\LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * GiftCardLoyaltyPoints constructor.
     * @param LoyaltyHelper $loyaltyHelper
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
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this|AbstractTotal
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $invoice->setLsPointsSpent(0);
        $invoice->setLsGiftCardAmountUsed(0);
        $invoice->setLsGiftCardNo(null);

        $pointsSpent = $invoice->getOrder()->getLsPointsSpent();
        $invoice->setLsPointsSpent($pointsSpent);

        $pointsEarn = $invoice->getOrder()->getLsPointsEarn();
        $invoice->setLsPointsEarn($pointsEarn);

        $giftCardAmount = $invoice->getOrder()->getLsGiftCardAmountUsed();
        $invoice->setLsGiftCardAmountUsed($giftCardAmount);

        $giftCardNo = $invoice->getOrder()->getLsGiftCardNo();
        $invoice->setLsGiftCardNo($giftCardNo);

        $pointsSpent = $pointsSpent * $this->loyaltyHelper->getPointRate();
        $grandTotalAmount=$invoice->getOrder()->getGrandTotal() - $invoice->getOrder()->getTaxAmount();
        $baseGrandTotalAmount = $invoice->getOrder()->getBaseGrandTotal() - $invoice->getOrder()->getTaxAmount();
        $invoice->setGrandTotal($grandTotalAmount);
        $invoice->setBaseGrandTotal($baseGrandTotalAmount);

        return $this;
    }
}
