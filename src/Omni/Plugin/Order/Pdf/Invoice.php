<?php

namespace Ls\Omni\Plugin\Order\Pdf;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Invoice pdf modification
 */
class Invoice
{
    /**
     * LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * Invoice constructor.
     * @param LoyaltyHelper $loyaltyHelper
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper
    ) {
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * @param $subject
     * @param array $invoices
     * @return array|mixed
     * @throws NoSuchEntityException
     */
    public function beforeGetPdf($subject, $invoices = [])
    {
        foreach ($invoices as $invoice) {
            if (!empty($invoice->getOrder()->getDocumentId())) {
                $invoice->getOrder()->setIncrementId($invoice->getOrder()->getDocumentId());
            }
            if ($invoice->getLsPointsSpent() > 0) {
                $pointRate = $this->loyaltyHelper->getPointRate();
                if ($pointRate > 0) {
                    $loyaltyAmount = -($invoice->getLsPointsSpent() * $pointRate);
                    $invoice->setLsPointsSpent($loyaltyAmount);
                } else {
                    $invoice->setLsPointsSpent(0);
                }
            }
            if ($invoice->getLsGiftCardAmountUsed() > 0) {
                $invoice->setLsGiftCardAmountUsed(-($invoice->getLsGiftCardAmountUsed()));
            }
        }
        return [$invoices];
    }
}
