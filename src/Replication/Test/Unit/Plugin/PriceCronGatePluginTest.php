<?php

declare(strict_types=1);

namespace Ls\Replication\Test\Unit\Plugin;

use Ls\Core\Model\LSR;
use Ls\Replication\Cron\ReplLscSalepriceviewTask;
use Ls\Replication\Cron\ReplLscSalesPriceTask;
use Ls\Replication\Plugin\PriceCronGatePlugin;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the PriceCronGatePlugin.
 *
 * The repl_price cron (ReplLscSalepriceviewTask) is the single price-fetch task and must never
 * be gated off; the legacy repl_sales_price cron (ReplLscSalesPriceTask) is retired and always
 * skipped. Behavior no longer depends on the UseSalesPrice config.
 */
class PriceCronGatePluginTest extends TestCase
{
    private const STORE_ID = 1;

    /**
     * @var LSR&\PHPUnit\Framework\MockObject\MockObject
     */
    private $lsr;

    /**
     * @var PriceCronGatePlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->lsr    = $this->createMock(LSR::class);
        $this->plugin = new PriceCronGatePlugin($this->lsr);
    }

    public function testSalePriceViewTaskAlwaysProceeds(): void
    {
        $subject = $this->createMock(ReplLscSalepriceviewTask::class);

        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
            return 'PROCEED_RESULT';
        };

        $result = $this->plugin->aroundFetchDataGivenStore($subject, $proceed, self::STORE_ID);

        $this->assertTrue($proceedCalled, 'The unified sale price view task must always proceed.');
        $this->assertSame('PROCEED_RESULT', $result);
    }

    public function testSalesPriceTaskIsAlwaysSkipped(): void
    {
        $subject = $this->createMock(ReplLscSalesPriceTask::class);

        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
            return 'PROCEED_RESULT';
        };

        $result = $this->plugin->aroundFetchDataGivenStore($subject, $proceed, self::STORE_ID);

        $this->assertNull($result, 'The retired sales price task must be skipped.');
        $this->assertFalse($proceedCalled, '$proceed must NOT be called for the retired sales price task.');
    }
}
