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
            $entryTotal = (float)array_sum(array_column(json_decode((string)$invoice->getLsPosDataEntries(), true) ?? [], 'amount'));
            if ($entryTotal > 0) {
                // Entry total is stored in ls_pos_data_entries; no separate column to set
            }
        }
        return [$invoices];
    }
}
