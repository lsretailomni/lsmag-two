<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Total\Quote;

use Ls\Omni\Helper\GiftCardHelper;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class GiftCardAmountUsed extends AbstractTotal
{
    public function __construct(
        private readonly GiftCardHelper $giftCardHelper
    ) {
    }

    /**
     * No deduction here — VoucherAmountUsed deducts the total of ALL entries.
     * This collector only provides the display segment.
     */
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);
        return $this;
    }

    /**
     * Return display segment for gift card portion from unified ls_pos_data_entries column.
     * Title is dynamically built as "<entry_type label> Redeemed".
     */
    public function fetch(Quote $quote, Total $total)
    {
        $raw    = $quote->getLsPosDataEntries();
        $amount = $this->giftCardHelper->getGiftCardTotal($raw);

        if ($amount > 0) {
            $cards = $this->giftCardHelper->getGiftCardEntries($raw);
            $count = count($cards);
            if ($count === 1) {
                $title = __(
                    '%1 - %2 Redeemed',
                    $cards[0]['entry_type'] ?? 'Gift Card',
                    $cards[0]['entry_no'] ?? ''
                );
            } else {
                $title = __('Gift Cards Redeemed (%1)', $count);
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
