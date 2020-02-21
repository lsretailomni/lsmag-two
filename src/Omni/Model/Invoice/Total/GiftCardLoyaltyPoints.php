<?php

namespace Ls\Omni\Model\Invoice\Total;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Class GiftCardLoyaltyPoints
 * @package Ls\Omni\Model
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
     * @param Creditmemo $creditmemo
     * @return $this|AbstractTotal
     */
    public function collect(Invoice $invoice)
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

        $grandTotalAmount     = $invoice->getOrder()->getGrandTotal();
        $baseGrandTotalAmount = $invoice->getOrder()->getBaseGrandTotal();
        $invoice->setGrandTotal($grandTotalAmount);
        $invoice->setBaseGrandTotal($baseGrandTotalAmount);
        return $this;
    }
}
