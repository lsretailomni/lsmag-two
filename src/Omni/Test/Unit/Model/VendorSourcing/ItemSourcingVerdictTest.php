<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Model\VendorSourcing;

use Ls\Omni\Model\VendorSourcing\ItemSourcingVerdict;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ItemSourcingVerdict} (ticket 89364, follow-on to ticket 89360's
 * checkout fallback): the new $itemId/$variantId trailing constructor params and accessors,
 * added purely additively so existing positional-arg call sites keep compiling untouched.
 */
class ItemSourcingVerdictTest extends TestCase
{
    public function testItemIdAndVariantIdAccessorsReturnConstructorValues(): void
    {
        $verdict = new ItemSourcingVerdict(
            1,
            'SKU-1',
            ItemSourcingVerdict::STATUS_VENDOR_SOURCED,
            [],
            null,
            null,
            'ITEM1',
            'VAR1'
        );

        $this->assertSame('ITEM1', $verdict->getItemId());
        $this->assertSame('VAR1', $verdict->getVariantId());
    }

    public function testItemIdAndVariantIdDefaultToNullWhenNotProvided(): void
    {
        $verdict = new ItemSourcingVerdict(
            1,
            'SKU-1',
            ItemSourcingVerdict::STATUS_VENDOR_SOURCED
        );

        $this->assertNull($verdict->getItemId());
        $this->assertNull($verdict->getVariantId());
    }

    /**
     * Additive-only regression check (solution plan requirement): existing positional-arg
     * construction (quoteItemId, sku, status, options, message, suggestedStore) must still
     * compile and behave identically without passing the two new trailing params.
     */
    public function testExistingPositionalConstructionStillWorksUnaffected(): void
    {
        $message = __('blocked');
        $suggestedStore = ['type' => 'ship', 'store_code' => 'S1'];

        $verdict = new ItemSourcingVerdict(
            5,
            'SKU-2',
            ItemSourcingVerdict::STATUS_BLOCKED,
            ['opt'],
            $message,
            $suggestedStore
        );

        $this->assertSame(5, $verdict->getQuoteItemId());
        $this->assertSame('SKU-2', $verdict->getSku());
        $this->assertSame(ItemSourcingVerdict::STATUS_BLOCKED, $verdict->getStatus());
        $this->assertSame(['opt'], $verdict->getOptions());
        $this->assertSame($message, $verdict->getMessage());
        $this->assertSame($suggestedStore, $verdict->getSuggestedStore());
        $this->assertNull($verdict->getItemId());
        $this->assertNull($verdict->getVariantId());
    }
}
