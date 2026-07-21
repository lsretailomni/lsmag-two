<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Total\Quote;

use Ls\Omni\Helper\GiftCardHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class VoucherNo extends AbstractTotal
{
    public function __construct(
        private readonly GiftCardHelper $giftCardHelper
    ) {
    }

    /**
     * Return only non-GIFTCARDNO (voucher) entries from unified column as a segment for JS rendering.
     */
    public function fetch(Quote $quote, Total $total)
    {
        $voucherEntriesJson = $this->giftCardHelper->getVoucherEntriesJson($quote->getLsPosDataEntries());
        if (!empty($voucherEntriesJson)) {
            return [
                'code'  => $this->getCode(),
                'title' => __('Voucher No'),
                'value' => $voucherEntriesJson,
            ];
        }
        return null;
    }
}
