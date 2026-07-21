<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Total\Quote;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class VoucherAmountUsed extends AbstractTotal
{
    /**
     * Decode JSON from ls_pos_data_entries and return total of ALL entries (gift cards + vouchers).
     */
    private function getTotalAmount(Quote $quote): float
    {
        $entries = json_decode((string)$quote->getLsPosDataEntries(), true);
        if (!is_array($entries)) {
            return 0.0;
        }
        return (float)array_sum(array_column($entries, 'amount'));
    }

    /**
     * Collect voucher amount and deduct from grand total
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        $amount = $this->getTotalAmount($quote);

        if ($amount > 0) {
            $total->setTotalAmount($this->getCode(), -$amount);
            $total->setBaseTotalAmount($this->getCode(), -$amount);
        }

        return $this;
    }

    /**
     * Fetch segment showing VOUCHER portion (non-GIFTCARDNO) for cart/checkout summary.
     * Title is dynamically built as "<entry_type label> Redeemed".
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $entries = json_decode((string)$quote->getLsPosDataEntries(), true);
        if (!is_array($entries)) {
            $entries = [];
        }
        $voucherEntries = array_values(array_filter(
            $entries,
            fn($e) => strtoupper($e['entry_type'] ?? '') !== 'GIFTCARDNO'
        ));
        $amount = (float)array_sum(array_column($voucherEntries, 'amount'));

        if ($amount > 0) {
            $count = count($voucherEntries);
            if ($count === 1) {
                $title = __(
                    '%1 - %2 Redeemed',
                    $voucherEntries[0]['entry_type'] ?? 'Voucher',
                    $voucherEntries[0]['entry_no'] ?? ''
                );
            } else {
                $title = __('Vouchers Redeemed (%1)', $count);
            }

            return [
                'code'  => $this->getCode(),
                'title' => $title,
                'value' => -$amount,
            ];
        }

        return null;
    }
}

