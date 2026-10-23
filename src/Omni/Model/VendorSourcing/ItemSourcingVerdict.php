<?php
declare(strict_types=1);

namespace Ls\Omni\Model\VendorSourcing;

use Magento\Framework\Phrase;

/**
 * Per-item outcome of the checkout vendor-sourcing fallback (ticket 89360): either the item is
 * fine to proceed as vendor-sourced (with its sourcing options and, for delivery, a suggested
 * store), or it is blocked with a customer-facing, per-SKU message (FR6).
 */
class ItemSourcingVerdict
{
    public const STATUS_VENDOR_SOURCED = 'vendor_sourced';

    public const STATUS_BLOCKED = 'blocked';

    /**
     * @param int|null $quoteItemId
     * @param string $sku
     * @param string $status one of self::STATUS_*
     * @param array $options VendorSourcingHelper::getOptions()-shaped rows, filtered to the
     *                       relevant fulfillment type
     * @param Phrase|null $message set only for blocked verdicts
     * @param array|null $suggestedStore FR9: the resolved sourcing location for #89364 to consume -
     *                                   same row shape as VendorSourcingHelper::getOptions(): type,
     *                                   store_code, store_name, days, date, priority. Null for C&C
     *                                   verdicts and for blocked verdicts.
     * @param string|null $itemId the LS Central item id (stock collection row's item_id) - used by
     *                            #89364 to correlate this verdict back to the CustomerOrderCreateCOLineV6
     *                            line actually sent to LS Central.
     * @param string|null $variantId the LS Central variant id (stock collection row's variant_id),
     *                                for the same correlation purpose as $itemId.
     */
    public function __construct(
        private readonly ?int $quoteItemId,
        private readonly string $sku,
        private readonly string $status,
        private readonly array $options = [],
        private readonly ?Phrase $message = null,
        private readonly ?array $suggestedStore = null,
        private readonly ?string $itemId = null,
        private readonly ?string $variantId = null
    ) {
    }

    public function getQuoteItemId(): ?int
    {
        return $this->quoteItemId;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getMessage(): ?Phrase
    {
        return $this->message;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * @return array|null the resolved sourcing location for #89364 to consume (FR9) -
     *                     same row shape as VendorSourcingHelper::getOptions(): type, store_code,
     *                     store_name, days, date, priority. Null for C&C verdicts and for blocked verdicts.
     */
    public function getSuggestedStore(): ?array
    {
        return $this->suggestedStore;
    }

    /**
     * The LS Central item id (stock collection row's item_id), for #89364's order-submission
     * correlation. Null when the verdict did not originate from a stock collection row.
     */
    public function getItemId(): ?string
    {
        return $this->itemId;
    }

    /**
     * The LS Central variant id (stock collection row's variant_id), for #89364's order-submission
     * correlation. Null when the verdict did not originate from a stock collection row.
     */
    public function getVariantId(): ?string
    {
        return $this->variantId;
    }
}
