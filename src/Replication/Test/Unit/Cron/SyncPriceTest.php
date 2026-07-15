<?php

declare(strict_types=1);

namespace Ls\Replication\Test\Unit\Cron;

use Ls\Core\Model\LSR;
use Ls\Replication\Api\ReplPriceRepositoryInterface;
use Ls\Replication\Cron\SyncPrice;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Replication\Model\ReplPrice;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for the outlive-based non-winner reset in {@see SyncPrice}.
 *
 * SyncPrice has a very large promoted constructor (inherited from ProductCreateTask),
 * so instances are created without invoking the constructor and the public dependencies
 * (replPriceRepository, replicationHelper, lsr, store) are assigned directly. The private
 * resetNonWinnerPrices() method is driven via reflection with a mocked repository that
 * returns crafted ReplPrice-like objects.
 */
class SyncPriceTest extends TestCase
{
    /**
     * @var SyncPrice
     */
    private $model;

    /**
     * @var ReplPriceRepositoryInterface&MockObject
     */
    private $replPriceRepository;

    /**
     * @var ReplicationHelper&MockObject
     */
    private $replicationHelper;

    /**
     * @var LSR&MockObject
     */
    private $lsr;

    /**
     * Filters passed to buildCriteriaForDirect() by the last resetNonWinnerPrices() call.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $capturedFilters = null;

    /**
     * The OR-group parameters ($parameter, $parameter2) passed to buildCriteriaForDirect()
     * by the last resetNonWinnerPrices() call.
     *
     * @var array<string, mixed>|null
     */
    private ?array $capturedOrParam1 = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $capturedOrParam2 = null;

    protected function setUp(): void
    {
        $this->written             = new \SplObjectStorage();
        $this->capturedFilters     = null;
        $this->capturedOrParam1    = null;
        $this->capturedOrParam2    = null;
        $this->replPriceRepository = $this->createMock(ReplPriceRepositoryInterface::class);
        $this->replicationHelper   = $this->createMock(ReplicationHelper::class);
        $this->lsr                 = $this->createMock(LSR::class);

        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getWebsiteId')->willReturn(1);

        // getScopeId() (public, inherited) reads $this->store->getWebsiteId(); keep it as-is.
        $this->model = (new \ReflectionClass(SyncPrice::class))->newInstanceWithoutConstructor();
        $this->model->replPriceRepository = $this->replPriceRepository;
        $this->model->replicationHelper   = $this->replicationHelper;
        $this->model->lsr                 = $this->lsr;
        $this->model->store               = $store;

        $this->lsr->method('getStoreConfig')->willReturn(1);
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->replicationHelper->method('buildCriteriaForDirect')
            ->willReturnCallback(
                function (
                    array $filters,
                    $pageSize = 100,
                    $excludeDeleted = true,
                    $param1 = null,
                    $param2 = null
                ) use ($searchCriteria) {
                    $this->capturedFilters  = $filters;
                    $this->capturedOrParam1 = $param1;
                    $this->capturedOrParam2 = $param2;
                    return $searchCriteria;
                }
            );
    }

    /**
     * Records setData() calls per record so tests can assert on the reset flags.
     *
     * @var \SplObjectStorage<object, array<string, mixed>>
     */
    private \SplObjectStorage $written;

    /**
     * Build a ReplPrice mock exposing the getters/setters resetNonWinnerPrices() uses.
     * The mock is a real ReplPriceInterface instance so the typed repository save() accepts it.
     * setData() calls are captured in {@see self::$written} keyed by the record.
     *
     * @return ReplPrice&MockObject
     */
    private function createRecord(
        ?string $endingDate,
        int $id = 1,
        string $itemId = 'ITEM01',
        ?string $variantId = null,
        ?string $startingDate = '2026-07-01',
        ?string $uom = null
    ) {
        $record = $this->getMockBuilder(ReplPrice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                ['getEndingDate', 'getStartingDate', 'getId', 'getItemId', 'getVariantId', 'getUnitOfMeasure', 'setData']
            )
            ->getMock();
        $record->method('getEndingDate')->willReturn($endingDate);
        $record->method('getStartingDate')->willReturn($startingDate);
        $record->method('getId')->willReturn($id);
        $record->method('getItemId')->willReturn($itemId);
        $record->method('getVariantId')->willReturn($variantId);
        $record->method('getUnitOfMeasure')->willReturn($uom);

        $this->written[$record] = [];
        $record->method('setData')->willReturnCallback(
            function (string $key, $value) use ($record) {
                $data = $this->written[$record];
                $data[$key] = $value;
                $this->written[$record] = $data;
                return $record;
            }
        );

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    private function writtenFor(object $record): array
    {
        return $this->written->contains($record) ? $this->written[$record] : [];
    }

    /**
     * Configure the repository to return the given non-winner records.
     *
     * @param object[] $items
     */
    private function stubNonWinners(array $items): void
    {
        $results = new class ($items) {
            /** @param object[] $items */
            public function __construct(private readonly array $items)
            {
            }

            /** @return object[] */
            public function getItems(): array
            {
                return $this->items;
            }
        };
        $this->replPriceRepository->method('getList')->willReturn($results);
    }

    private function invokeReset(object $winner): void
    {
        $method = new ReflectionMethod(SyncPrice::class, 'resetNonWinnerPrices');
        $method->setAccessible(true);
        $method->invoke($this->model, $winner);
    }

    public function testDatedLoserThatOutlivesWinnerIsReset(): void
    {
        // Winner ends 15 Jul 2026; loser B ends 2035 (outlives), on a different price list.
        $winner = $this->createRecord('2026-07-15', 100);
        $loserB = $this->createRecord('2035-07-01', 200);
        $this->stubNonWinners([$loserB]);

        $this->replPriceRepository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($loserB));

        $this->invokeReset($winner);

        $this->assertSame(0, $this->writtenFor($loserB)['processed']);
        $this->assertSame(1, $this->writtenFor($loserB)['is_updated']);

        // Lock in the cross-price-list scope: the reset query must NOT filter by PriceListCode.
        $this->assertIsArray($this->capturedFilters);
        $fields = array_column($this->capturedFilters, 'field');
        $this->assertNotContains(
            'PriceListCode',
            $fields,
            'resetNonWinnerPrices() must match losers across any price list — no PriceListCode filter.'
        );
    }

    public function testSameDayLoserWithLaterTimeIsNotReset(): void
    {
        // Winner ends 15 Jul (date-only); loser ends 15 Jul 18:00 (same calendar day, later time).
        // Day-granularity comparison ⇒ equal ⇒ loser does NOT outlive ⇒ not reset.
        $winner     = $this->createRecord('2026-07-15', 100);
        $sameDay    = $this->createRecord('2026-07-15T18:00:00', 700);
        $nextDay    = $this->createRecord('2026-07-16T09:00:00', 800);
        $this->stubNonWinners([$sameDay, $nextDay]);

        // Only the genuinely-later (next-day) loser is saved.
        $this->replPriceRepository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($nextDay));

        $this->invokeReset($winner);

        $this->assertArrayNotHasKey('processed', $this->writtenFor($sameDay));
        $this->assertSame(0, $this->writtenFor($nextDay)['processed']);
        $this->assertSame(1, $this->writtenFor($nextDay)['is_updated']);
    }

    public function testDatedLoserThatExpiresBeforeWinnerIsNotReset(): void
    {
        // Winner ends 15 Jul 2026; loser C ends 10 Jul 2026 (before winner) → not reset.
        $winner = $this->createRecord('2026-07-15', 100);
        $loserC = $this->createRecord('2026-07-10', 300);
        $this->stubNonWinners([$loserC]);

        $this->replPriceRepository->expects($this->never())->method('save');

        $this->invokeReset($winner);

        $this->assertArrayNotHasKey('processed', $this->writtenFor($loserC));
        $this->assertArrayNotHasKey('is_updated', $this->writtenFor($loserC));
    }

    public function testOpenEndedLoserIsStillReset(): void
    {
        // Regression: an open-ended loser (blank dates) never expires → always outlives.
        $winner = $this->createRecord('2026-07-15', 100);
        $loser  = $this->createRecord(null, 400, 'ITEM01', null, null);
        $this->stubNonWinners([$loser]);

        $this->replPriceRepository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($loser));

        $this->invokeReset($winner);

        $this->assertSame(0, $this->writtenFor($loser)['processed']);
        $this->assertSame(1, $this->writtenFor($loser)['is_updated']);
    }

    public function testSentinelEndingDateLoserIsTreatedAsOpenEndedAndReset(): void
    {
        // LS Central sentinel ending date (1900-01-01) ⇒ never expires ⇒ reset.
        $winner = $this->createRecord('2026-07-15', 100);
        $loser  = $this->createRecord('1900-01-01T00:00:00', 500);
        $this->stubNonWinners([$loser]);

        $this->replPriceRepository->expects($this->once())->method('save');

        $this->invokeReset($winner);

        $this->assertSame(0, $this->writtenFor($loser)['processed']);
        $this->assertSame(1, $this->writtenFor($loser)['is_updated']);
    }

    public function testOpenEndedWinnerReturnsWithoutQueryingOrResetting(): void
    {
        // Open-ended winner (blank dates) → base price restored → no resets, no query.
        $winner = $this->createRecord(null, 100, 'ITEM01', null, null);

        $this->replPriceRepository->expects($this->never())->method('getList');
        $this->replPriceRepository->expects($this->never())->method('save');

        $this->invokeReset($winner);
    }

    public function testWinnerWithBlankEndingDateNeverExpiresSoNoLoserIsReset(): void
    {
        // Winner has a start date but no ending date → never expires → nothing to hand off.
        // Winner is not open-ended (it has a StartingDate), so the query still runs, but no
        // loser can outlive a never-expiring winner.
        $winner = $this->createRecord(null, 100, 'ITEM01', null, '2026-07-01');
        $loser  = $this->createRecord('2035-07-01', 600);
        $this->stubNonWinners([$loser]);

        $this->replPriceRepository->expects($this->never())->method('save');

        $this->invokeReset($winner);

        $this->assertArrayNotHasKey('processed', $this->writtenFor($loser));
    }

    /**
     * Section D: for an explicit-UOM winner the reset must match losers whose UOM equals the
     * winner's UOM OR is NULL (the shared blank-UOM fallback line), expressed as an OR group.
     * A different explicit UOM (e.g. a valid PCS winner) must not be caught by that group.
     */
    public function testResetMatchesWinnerUomOrBlankFallbackWhenWinnerHasExplicitUom(): void
    {
        $winner = $this->createRecord('2026-07-15', 100, 'ITEM01', null, '2026-07-01', 'PACK');
        $this->stubNonWinners([]);

        $this->invokeReset($winner);

        // UnitOfMeasure is NOT a plain AND filter; it is an OR group (param/param2).
        $this->assertNull($this->filterFor('UnitOfMeasure'));
        $this->assertSame(
            ['field' => 'UnitOfMeasure', 'value' => 'PACK', 'condition_type' => 'eq'],
            $this->capturedOrParam1
        );
        $this->assertSame(
            ['field' => 'UnitOfMeasure', 'value' => true, 'condition_type' => 'null'],
            $this->capturedOrParam2
        );
    }

    /**
     * M1 regression: an explicit-UOM (PCS) dated winner ending 15-Jul must still re-queue a
     * blank-UOM loser ending 2035 — the blank line is the PCS product's fallback after expiry.
     * The OR group (uom=PCS OR uom IS NULL) admits the blank loser; outlivesWinner() resets it.
     */
    public function testExplicitUomWinnerStillResetsBlankUomFallbackLoser(): void
    {
        $winner = $this->createRecord('2026-07-15', 100, 'ITEM01', null, '2026-07-01', 'PCS');
        // Blank-UOM loser (uom null) ending 2035 — outlives the winner.
        $blankLoser = $this->createRecord('2035-07-01', 900, 'ITEM01', null, '2026-07-01', null);
        $this->stubNonWinners([$blankLoser]);

        $this->replPriceRepository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($blankLoser));

        $this->invokeReset($winner);

        $this->assertSame(0, $this->writtenFor($blankLoser)['processed']);
        $this->assertSame(1, $this->writtenFor($blankLoser)['is_updated']);
    }

    /**
     * A base/blank-UOM winner (UnitOfMeasure NULL) matches losers with NULL UOM only —
     * a plain AND null filter, no OR group (nothing more specific to fall back to).
     */
    public function testResetUsesNullUomFilterWhenWinnerHasBlankUom(): void
    {
        $winner = $this->createRecord('2026-07-15', 100, 'ITEM01', null, '2026-07-01', null);
        $this->stubNonWinners([]);

        $this->invokeReset($winner);

        $this->assertNull($this->capturedOrParam1);
        $this->assertNull($this->capturedOrParam2);
        $uomFilter = $this->filterFor('UnitOfMeasure');
        $this->assertNotNull($uomFilter);
        $this->assertTrue($uomFilter['value']);
        $this->assertSame('null', $uomFilter['condition_type']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function filterFor(string $field): ?array
    {
        foreach ($this->capturedFilters ?? [] as $filter) {
            if (($filter['field'] ?? null) === $field) {
                return $filter;
            }
        }

        return null;
    }

    // ---------------------------------------------------------------------
    // getPrice() — per-product-UOM selection with base/blank fallback (Section B)
    // ---------------------------------------------------------------------

    /**
     * Build a product-data stub exposing getData() for the attributes getPrice() reads.
     */
    private function createProduct(string $itemId, ?string $variantId, string $uom): object
    {
        return new class ($itemId, $variantId, $uom) {
            public function __construct(
                private string $itemId,
                private ?string $variantId,
                private string $uom
            ) {
            }

            public function getData(string $key)
            {
                return match ($key) {
                    LSR::LS_ITEM_ID_ATTRIBUTE_CODE    => $this->itemId,
                    LSR::LS_VARIANT_ID_ATTRIBUTE_CODE => $this->variantId,
                    'uom'                             => $this->uom,
                    default                           => null,
                };
            }
        };
    }

    /**
     * A lightweight price entry tagged with a label so tests can assert which key won.
     */
    private function priceEntry(string $label): object
    {
        return new class ($label) {
            public function __construct(public string $label)
            {
            }
        };
    }

    /**
     * Point the helper's base-UOM accessor at a fixed base/sales pair for getPrice().
     */
    private function stubBaseUom(string $base, string $sales = ''): void
    {
        $this->replicationHelper->method('getItemBaseAndSalesUom')
            ->willReturn(['base' => $base, 'sales' => $sales]);
    }

    public function testGetPricePicksExactUomLineForPcsProduct(): void
    {
        $this->stubBaseUom('PCS');
        $list = [
            'ITEM01--PCS'  => $this->priceEntry('PCS'),
            'ITEM01--PACK' => $this->priceEntry('PACK'),
        ];

        $result = $this->model->getPrice($this->createProduct('ITEM01', null, 'PCS'), $list);

        $this->assertSame('PCS', $result->label);
    }

    public function testGetPricePicksExactUomLineForPackProduct(): void
    {
        $this->stubBaseUom('PCS');
        $list = [
            'ITEM01--PCS'  => $this->priceEntry('PCS'),
            'ITEM01--PACK' => $this->priceEntry('PACK'),
        ];

        $result = $this->model->getPrice($this->createProduct('ITEM01', null, 'PACK'), $list);

        $this->assertSame('PACK', $result->label);
    }

    public function testGetPricePackProductFallsBackToBaseUomLine(): void
    {
        // Only a base (PCS) line exists; the PACK product must fan out to it.
        $this->stubBaseUom('PCS');
        $list = ['ITEM01--PCS' => $this->priceEntry('PCS')];

        $result = $this->model->getPrice($this->createProduct('ITEM01', null, 'PACK'), $list);

        $this->assertSame('PCS', $result->label);
    }

    public function testGetPricePackProductFallsBackToBlankUomLine(): void
    {
        // Only a blank-UOM (item-level) line exists.
        $this->stubBaseUom('PCS');
        $list = ['ITEM01--' => $this->priceEntry('BLANK')];

        $result = $this->model->getPrice($this->createProduct('ITEM01', null, 'PACK'), $list);

        $this->assertSame('BLANK', $result->label);
    }

    public function testGetPriceBaseFallbackWhenSalesUomDiffersFromBase(): void
    {
        // Base=PCS, Sales=BOX: the base product's own uom is BOX, but only a PCS line
        // exists → it must fall back to the real base-UOM (PCS) line.
        $this->stubBaseUom('PCS', 'BOX');
        $list = ['ITEM01--PCS' => $this->priceEntry('PCS')];

        $result = $this->model->getPrice($this->createProduct('ITEM01', null, 'BOX'), $list);

        $this->assertSame('PCS', $result->label);
    }

    public function testGetPriceReturnsNullWhenNothingMatches(): void
    {
        $this->stubBaseUom('PCS');
        $list = ['ITEM01--CASE' => $this->priceEntry('CASE')];

        $result = $this->model->getPrice($this->createProduct('ITEM01', null, 'PACK'), $list);

        $this->assertNull($result);
    }

    public function testGetPriceVariantProductFallsBackToNoVariantLines(): void
    {
        // No variant-specific line for VAR1; fall back to the no-variant scope with the
        // same UOM priority (exact PACK line under the "ITEM01--…" keyspace).
        $this->stubBaseUom('PCS');
        $list = ['ITEM01--PACK' => $this->priceEntry('NOVARIANT-PACK')];

        $result = $this->model->getPrice($this->createProduct('ITEM01', 'VAR1', 'PACK'), $list);

        $this->assertSame('NOVARIANT-PACK', $result->label);
    }

    public function testGetPricePackOnlyLineDoesNotPricePcsProduct(): void
    {
        // Regression: a PACK-only line must not leak onto the PCS product. With no PCS,
        // base(PCS)-key, or blank line present, the PCS product resolves to null and keeps
        // its existing price.
        $this->stubBaseUom('PCS');
        $list = ['ITEM01--PACK' => $this->priceEntry('PACK')];

        $result = $this->model->getPrice($this->createProduct('ITEM01', null, 'PCS'), $list);

        $this->assertNull($result);
    }

    // ---------------------------------------------------------------------
    // processProductPrice() — no cross-UOM leak: a product with no matching
    // line is skipped, not overwritten with the raw price row (Section A/C1)
    // ---------------------------------------------------------------------

    public function testProcessProductPricePackLineDoesNotOverwritePcsProduct(): void
    {
        // A PACK-specific price row is processed. The product array (from the item+variant
        // lookup) contains BOTH the PCS and PACK products. Only the PACK product has a
        // matching line — the PCS product must be left untouched (no saveAttribute).
        $packLine = $this->getMockBuilder(ReplPrice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId', 'getUnitPriceInclVat', 'getId', 'getStartingDate', 'getEndingDate', 'getStatus', 'addData'])
            ->getMock();
        $packLine->method('getItemId')->willReturn('ITEM01');
        $packLine->method('getUnitPriceInclVat')->willReturn(25.0);
        $packLine->method('getId')->willReturn(55);
        $packLine->method('getStartingDate')->willReturn('');
        $packLine->method('getEndingDate')->willReturn('');
        $packLine->method('getStatus')->willReturn('1');

        $pcsProduct  = $this->createPriceableProduct('ITEM01', null, 'PCS', 10.0);
        $packProduct = $this->createPriceableProduct('ITEM01', null, 'PACK', 20.0);

        // Partial-mock the collaborators processProductPrice() reaches beyond price selection.
        $model = $this->getMockBuilder(SyncPrice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemPriceList', 'getPrice', 'resetNonWinnerPrices'])
            ->getMock();
        // Winner exists only for the PACK product; PCS resolves to null.
        $model->method('getItemPriceList')->willReturn(['ITEM01--PACK' => $packLine]);
        $model->method('getPrice')->willReturnCallback(
            fn($product) => $product === $packProduct ? $packLine : null
        );

        $resourceModel = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveAttribute'])
            ->getMock();
        // saveAttribute must be invoked for the PACK product only, NEVER for the PCS product.
        $resourceModel->expects($this->once())
            ->method('saveAttribute')
            ->with($this->identicalTo($packProduct), 'price');
        $model->productResourceModel = $resourceModel;

        $replRepo = $this->createMock(ReplPriceRepositoryInterface::class);
        $model->replPriceRepository = $replRepo;
        $model->replicationHelper   = $this->replicationHelper;
        $this->replicationHelper->method('getDateTime')->willReturn('2026-07-15 00:00:00');
        $model->processed = [];

        $method = new ReflectionMethod(SyncPrice::class, 'processProductPrice');
        $method->setAccessible(true);
        $method->invoke($model, [$pcsProduct, $packProduct], $packLine);

        // The PCS product's price is untouched (still its original 10.0, not the PACK 25.0).
        // The saveAttribute once()/with(packProduct) expectation above proves PCS was skipped.
        $this->assertSame(10.0, (float)$pcsProduct->getData('price'));
    }

    /**
     * Product stub (a real DataObject so the typed saveAttribute() signature accepts it),
     * seeded with the ls_item_id / ls_variant_id / uom / price data processProductPrice() reads.
     */
    private function createPriceableProduct(
        string $itemId,
        ?string $variantId,
        string $uom,
        float $price
    ): \Magento\Framework\DataObject {
        return new \Magento\Framework\DataObject([
            LSR::LS_ITEM_ID_ATTRIBUTE_CODE    => $itemId,
            LSR::LS_VARIANT_ID_ATTRIBUTE_CODE => $variantId,
            'uom'                             => $uom,
            'price'                           => $price,
        ]);
    }

    // ---------------------------------------------------------------------
    // process() — base-UOM row fetches ALL UOM products (empty uom arg) (Section A)
    // ---------------------------------------------------------------------

    public function testProcessFetchesAllUomProductsWithEmptyUomArgument(): void
    {
        $capturedUom = 'UNSET';
        $this->replicationHelper->method('getProductDataByIdentificationAttributes')
            ->willReturnCallback(function ($itemId, $variantId, $uom) use (&$capturedUom) {
                $capturedUom = $uom;
                return null; // stop before processProductPrice
            });

        // logger (typed Ls\Replication\Logger\Logger) is only touched on the exception path,
        // which this happy-path test does not reach, so it is intentionally left unset.
        $this->model->processed = [];

        // Active price with blank dates ⇒ valid and not future ⇒ reaches the product lookup.
        $price = $this->getMockBuilder(ReplPrice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getStatus', 'getStartingDate', 'getEndingDate', 'getItemId', 'getVariantId', 'getUnitOfMeasure'])
            ->getMock();
        $price->method('getId')->willReturn(1);
        $price->method('getStatus')->willReturn('1');
        $price->method('getStartingDate')->willReturn('');
        $price->method('getEndingDate')->willReturn('');
        $price->method('getItemId')->willReturn('40020');
        $price->method('getVariantId')->willReturn('000');
        $price->method('getUnitOfMeasure')->willReturn('PCS'); // base-UOM row

        $this->model->process([$price]);

        $this->assertSame(
            '',
            $capturedUom,
            'process() must pass an empty UOM so getProductDataByIdentificationAttributes returns all UOM products.'
        );
    }
}
