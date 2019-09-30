<?php

namespace Ls\Omni\Model\Creditmemo\Total;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

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

        $pointsSpent *= $this->loyaltyHelper->getPointRate();
        $grandTotalAmount=$creditmemo->getOrder()->getGrandTotal()
            - $creditmemo->getOrder()->getShippingAmount() - $creditmemo->getOrder()->getTaxAmount();
        $baseGrandTotalAmount = $creditmemo->getOrder()->getBaseGrandTotal()
            - $creditmemo->getOrder()->getShippingAmount() - $creditmemo->getOrder()->getTaxAmount();
        $creditmemo->setGrandTotal($grandTotalAmount);
        $creditmemo->setBaseGrandTotal($baseGrandTotalAmount);

        return $this;
    }
}
