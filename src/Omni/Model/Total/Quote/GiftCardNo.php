<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Total\Quote;

use Ls\Omni\Helper\GiftCardHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class GiftCardNo extends AbstractTotal
{
    public function __construct(
        private readonly GiftCardHelper $giftCardHelper
    ) {
    }

    /**
     * Return only the GIFTCARDNO entries from unified column as a segment for JS rendering.
     */
    public function fetch(Quote $quote, Total $total)
    {
        $giftCardEntriesJson = $this->giftCardHelper->getGiftCardEntriesJson($quote->getLsPosDataEntries());
        if (!empty($giftCardEntriesJson)) {
            return [
                'code'  => $this->getCode(),
                'title' => __('Gift Card No'),
                'value' => $giftCardEntriesJson,
            ];
        }
        return null;
    }
}
