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

    protected function setUp(): void
    {
        $this->written             = new \SplObjectStorage();
        $this->capturedFilters     = null;
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
            ->willReturnCallback(function (array $filters) use ($searchCriteria) {
                $this->capturedFilters = $filters;
                return $searchCriteria;
            });
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
        ?string $startingDate = '2026-07-01'
    ) {
        $record = $this->getMockBuilder(ReplPrice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEndingDate', 'getStartingDate', 'getId', 'getItemId', 'getVariantId', 'setData'])
            ->getMock();
        $record->method('getEndingDate')->willReturn($endingDate);
        $record->method('getStartingDate')->willReturn($startingDate);
        $record->method('getId')->willReturn($id);
        $record->method('getItemId')->willReturn($itemId);
        $record->method('getVariantId')->willReturn($variantId);

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
}
