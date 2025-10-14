<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Order\Pdf;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Invoice pdf modification
 */
class Invoice
{
    /**
     * @param LoyaltyHelper $loyaltyHelper
     */
    public function __construct(
        public LoyaltyHelper $loyaltyHelper
    ) {
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
                $loyaltyAmount = -$this->loyaltyHelper->getLsPointsDiscount($invoice->getLsPointsSpent());
                $invoice->setLsPointsSpent($loyaltyAmount);
            } else {
                $invoice->setLsPointsSpent(0);
            }
            if ($invoice->getLsGiftCardAmountUsed() > 0) {
                $invoice->setLsGiftCardAmountUsed(-($invoice->getLsGiftCardAmountUsed()));
            }
        }
        return [$invoices];
    }
}
