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
 */
class PriceCronGatePluginTest extends TestCase
{
    private const CONFIG_PATH_USE_SALES_PRICE = 'ls_mag/replication/use_sales_price';

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

    public function testAroundFetchDataGivenStore_SalePriceViewTask_WhenUseSalesPriceYes_SkipsAndReturnsNull(): void
    {
        $subject = $this->createMock(ReplLscSalepriceviewTask::class);
        $this->lsr->method('getStoreConfig')
            ->with(self::CONFIG_PATH_USE_SALES_PRICE, self::STORE_ID)
            ->willReturn('1');

        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
            return 'PROCEED_RESULT';
        };

        $result = $this->plugin->aroundFetchDataGivenStore($subject, $proceed, self::STORE_ID);

        $this->assertNull($result);
        $this->assertFalse($proceedCalled, '$proceed must NOT be called when the sale price view task is gated off.');
    }

    public function testAroundFetchDataGivenStore_SalePriceViewTask_WhenUseSalesPriceNo_CallsProced(): void
    {
        $subject = $this->createMock(ReplLscSalepriceviewTask::class);
        $this->lsr->method('getStoreConfig')
            ->with(self::CONFIG_PATH_USE_SALES_PRICE, self::STORE_ID)
            ->willReturn('0');

        $proceed = fn () => 'PROCEED_RESULT';

        $result = $this->plugin->aroundFetchDataGivenStore($subject, $proceed, self::STORE_ID);

        $this->assertSame('PROCEED_RESULT', $result);
    }

    public function testAroundFetchDataGivenStore_SalesPriceTask_WhenUseSalesPriceNo_SkipsAndReturnsNull(): void
    {
        $subject = $this->createMock(ReplLscSalesPriceTask::class);
        $this->lsr->method('getStoreConfig')
            ->with(self::CONFIG_PATH_USE_SALES_PRICE, self::STORE_ID)
            ->willReturn('0');

        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
            return 'PROCEED_RESULT';
        };

        $result = $this->plugin->aroundFetchDataGivenStore($subject, $proceed, self::STORE_ID);

        $this->assertNull($result);
        $this->assertFalse($proceedCalled, '$proceed must NOT be called when the sales price task is gated off.');
    }

    public function testAroundFetchDataGivenStore_SalesPriceTask_WhenUseSalesPriceYes_CallsProced(): void
    {
        $subject = $this->createMock(ReplLscSalesPriceTask::class);
        $this->lsr->method('getStoreConfig')
            ->with(self::CONFIG_PATH_USE_SALES_PRICE, self::STORE_ID)
            ->willReturn('1');

        $proceed = fn () => 'PROCEED_RESULT';

        $result = $this->plugin->aroundFetchDataGivenStore($subject, $proceed, self::STORE_ID);

        $this->assertSame('PROCEED_RESULT', $result);
    }
}
