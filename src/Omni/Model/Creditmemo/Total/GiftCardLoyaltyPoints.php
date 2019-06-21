<?php

namespace Ls\Omni\Model\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;
use Ls\Omni\Helper\LoyaltyHelper;

/**
 * Class GiftCardLoyaltyPoints
 * @package Ls\Omni\Model
 */
class GiftCardLoyaltyPoints extends AbstractTotal
{

    /**
     * @var Ls\Omni\Helper\LoyaltyHelper
     */
    public $loyaltyHelper;

    public function __construct(
        LoyaltyHelper $loyaltyHelper
    )
    {
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this|AbstractTotal
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setLsPointsSpent(0);
        $creditmemo->setLsGiftCardAmountUsed(0);
        $creditmemo->setLsGiftCardNo(null);

        $pointsSpent = $creditmemo->getOrder()->getLsPointsSpent();
        $creditmemo->setLsPointsSpent($pointsSpent);

        $pointsEarn = $creditmemo->getOrder()->getLsPointsEarn();
        $creditmemo->setLsPointsEarn($pointsEarn);

        $giftCardAmount = $creditmemo->getOrder()->getLsGiftCardAmountUsed();
        $creditmemo->setLsGiftCardAmountUsed($giftCardAmount);

        $giftCardNo = $creditmemo->getOrder()->getLsGiftCardNo();
        $creditmemo->setLsGiftCardNo($giftCardNo);

        $pointsSpent=$pointsSpent*$this->loyaltyHelper->getPointRate();

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $giftCardAmount - $pointsSpent);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $giftCardAmount - $pointsSpent);

        return $this;
    }
}
