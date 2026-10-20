<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Model\VendorSourcing;

use Ls\Omni\Helper\VendorSourcingHelper;
use Ls\Omni\Model\VendorSourcing\PickupStoreIntersectionFilter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see PickupStoreIntersectionFilter} (ticket 89360, D5/FR8, solution plan §7.1/§12).
 *
 * A candidate pickup store is only valid if it (a) has stock of every normal item (the
 * qty-satisfied store set, computed elsewhere) AND (b) is a collect-type sourcing store for
 * EVERY vendor-sourced item in the cart - a single C&C order ships from one physical store, so
 * every line (normal or sourced) must be satisfiable from the same store.
 *
 * Options rows follow VendorSourcingHelper::getOptions()'s shape: type, store_code, store_name,
 * days, date, priority. Only 'type' and 'store_code' are relevant to this filter.
 */
class PickupStoreIntersectionFilterTest extends TestCase
{
    private PickupStoreIntersectionFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new PickupStoreIntersectionFilter();
    }

    /**
     * @param string $storeCode
     * @return array
     */
    private function collectOption(string $storeCode): array
    {
        return [
            'type' => VendorSourcingHelper::TYPE_COLLECT,
            'store_code' => $storeCode,
            'store_name' => 'Store ' . $storeCode,
            'days' => 2,
            'date' => '2026-10-21',
            'priority' => 1,
        ];
    }

    /**
     * @return array
     */
    private function shipOnlyOption(): array
    {
        return [
            'type' => VendorSourcingHelper::TYPE_SHIP,
            'store_code' => null,
            'store_name' => null,
            'days' => 5,
            'date' => '2026-10-24',
            'priority' => 1,
        ];
    }

    /**
     * No vendor-sourced items present (empty options list) - the qty-satisfied store set passes
     * through completely unchanged (no-op, AC1/AC2-equivalent for the store list).
     */
    public function testFilterPassesThroughUnchangedWhenNoVendorSourcedItems(): void
    {
        $qtySatisfied = ['S1', 'S2', 'S3'];

        $result = $this->filter->filter($qtySatisfied, []);

        $this->assertSame($qtySatisfied, $result);
    }

    /**
     * A single vendor-sourced item narrows the qty-satisfied set down to only the stores that
     * are also collect-type sourcing stores for that item.
     */
    public function testFilterNarrowsToSingleVendorSourcedItemsCollectStores(): void
    {
        $qtySatisfied = ['S1', 'S2', 'S3'];
        $vendorItemOptions = [
            $this->collectOption('S1'),
            $this->collectOption('S2'),
            $this->shipOnlyOption(),
        ];

        $result = $this->filter->filter($qtySatisfied, [$vendorItemOptions]);

        $this->assertSame(['S1', 'S2'], array_values($result));
    }

    /**
     * Two vendor-sourced items in the same cart must intersect against EACH OTHER's collect-type
     * store sets as well as against the qty-satisfied set - a store must work for every line.
     */
    public function testFilterIntersectsAcrossMultipleVendorSourcedItems(): void
    {
        $qtySatisfied = ['S1', 'S2', 'S3'];
        $firstItemOptions = [
            $this->collectOption('S1'),
            $this->collectOption('S2'),
        ];
        $secondItemOptions = [
            $this->collectOption('S2'),
            $this->collectOption('S4'),
        ];

        $result = $this->filter->filter($qtySatisfied, [$firstItemOptions, $secondItemOptions]);

        $this->assertSame(['S2'], array_values($result));
    }

    /**
     * A vendor-sourced item with zero collect-type codes (e.g. ship-only options, no eligible
     * collect-type row) must empty the result entirely - there is no store that can collect it.
     */
    public function testFilterEmptiesResultWhenVendorSourcedItemHasNoCollectTypeCodes(): void
    {
        $qtySatisfied = ['S1', 'S2', 'S3'];
        $vendorItemOptions = [
            $this->shipOnlyOption(),
        ];

        $result = $this->filter->filter($qtySatisfied, [$vendorItemOptions]);

        $this->assertSame([], array_values($result));
    }

    /**
     * When one of two vendor-sourced items has no collect-type codes, the overall intersection
     * must still empty out (every line must be satisfiable from the same store).
     */
    public function testFilterEmptiesResultWhenAnyVendorSourcedItemAmongManyHasNoCollectTypeCodes(): void
    {
        $qtySatisfied = ['S1', 'S2', 'S3'];
        $firstItemOptions = [$this->collectOption('S1'), $this->collectOption('S2')];
        $secondItemOptions = [$this->shipOnlyOption()];

        $result = $this->filter->filter($qtySatisfied, [$firstItemOptions, $secondItemOptions]);

        $this->assertSame([], array_values($result));
    }
}
