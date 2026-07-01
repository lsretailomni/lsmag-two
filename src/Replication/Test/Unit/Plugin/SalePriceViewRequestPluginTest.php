<?php

declare(strict_types=1);

namespace Ls\Replication\Test\Unit\Plugin;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\CentralEcommerce\Operation\PriceListLine;
use Ls\Replication\Cron\ReplLscSalepriceviewTask;
use Ls\Replication\Plugin\SalePriceViewRequestPlugin;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SalePriceViewRequestPlugin.
 */
class SalePriceViewRequestPluginTest extends TestCase
{
    private const CONFIG_PATH_USE_SALES_PRICE = 'ls_mag/replication/use_sales_price';

    /**
     * @var LSR&\PHPUnit\Framework\MockObject\MockObject
     */
    private $lsr;

    /**
     * @var PriceListLine&\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceListLine;

    /**
     * @var ReplLscSalepriceviewTask&\PHPUnit\Framework\MockObject\MockObject
     */
    private $subject;

    /**
     * @var SalePriceViewRequestPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->lsr           = $this->createMock(LSR::class);
        $this->priceListLine = $this->createMock(PriceListLine::class);
        $this->subject       = $this->createMock(ReplLscSalepriceviewTask::class);

        $this->plugin = new SalePriceViewRequestPlugin(
            $this->lsr,
            $this->priceListLine
        );
    }

    public function testAroundMakeRequest_NoConfig_OldVersion_ReturnsPriceListLineOperation(): void
    {
        $this->lsr->method('getStoreConfig')
            ->with(self::CONFIG_PATH_USE_SALES_PRICE, null)
            ->willReturn('0');
        $this->lsr->method('getCentralVersion')->willReturn('25.0.0.0');

        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
            return 'PROCEED_RESULT';
        };

        $result = $this->plugin->aroundMakeRequest($this->subject, $proceed);

        $this->assertFalse($proceedCalled, '$proceed must NOT be called for old version with UseSalesPrice=No.');
        $this->assertSame(
            $this->priceListLine,
            $result,
            'Plugin must return the PriceListLine operation instance.'
        );
    }

    public function testAroundMakeRequest_NoConfig_NewVersion_CallsProced(): void
    {
        $this->lsr->method('getStoreConfig')
            ->with(self::CONFIG_PATH_USE_SALES_PRICE, null)
            ->willReturn('0');
        $this->lsr->method('getCentralVersion')->willReturn('28.0.0.0');

        $proceed = fn () => 'PROCEED_RESULT';

        $result = $this->plugin->aroundMakeRequest($this->subject, $proceed);

        $this->assertSame('PROCEED_RESULT', $result);
    }

    public function testAroundMakeRequest_YesConfig_CallsProced(): void
    {
        $this->lsr->method('getStoreConfig')
            ->with(self::CONFIG_PATH_USE_SALES_PRICE, null)
            ->willReturn('1');
        $this->lsr->method('getCentralVersion')->willReturn('25.0.0.0');

        $proceed = fn () => 'PROCEED_RESULT';

        $result = $this->plugin->aroundMakeRequest($this->subject, $proceed);

        $this->assertSame('PROCEED_RESULT', $result);
    }
}
