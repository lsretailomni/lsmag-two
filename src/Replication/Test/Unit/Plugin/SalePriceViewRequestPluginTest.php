<?php

declare(strict_types=1);

namespace Ls\Replication\Test\Unit\Plugin;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\CentralEcommerce\Operation\PriceListLine;
use Ls\Omni\Client\CentralEcommerce\Operation\SalesPrice;
use Ls\Replication\Cron\ReplLscSalepriceviewTask;
use Ls\Replication\Plugin\SalePriceViewRequestPlugin;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SalePriceViewRequestPlugin routing.
 */
class SalePriceViewRequestPluginTest extends TestCase
{
    private const CONFIG_PATH_USE_SALES_PRICE = 'ls_mag/replication/use_sales_price';
    private const STORE_ID = 7;

    /**
     * @var LSR&\PHPUnit\Framework\MockObject\MockObject
     */
    private $lsr;

    /**
     * @var PriceListLine&\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceListLine;

    /**
     * @var SalesPrice&\PHPUnit\Framework\MockObject\MockObject
     */
    private $salesPrice;

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
        $this->salesPrice    = $this->createMock(SalesPrice::class);
        $this->subject       = $this->createMock(ReplLscSalepriceviewTask::class);

        $this->lsr->method('getCurrentStoreId')->willReturn(self::STORE_ID);

        $this->plugin = new SalePriceViewRequestPlugin(
            $this->lsr,
            $this->priceListLine,
            $this->salesPrice
        );
    }

    /**
     * @param string $useSalesPrice
     * @param string $centralVersion
     * @return void
     */
    private function configure(string $useSalesPrice, string $centralVersion): void
    {
        $this->lsr->method('getStoreConfig')
            ->with(self::CONFIG_PATH_USE_SALES_PRICE, self::STORE_ID)
            ->willReturn($useSalesPrice);
        $this->lsr->method('getCentralVersion')
            ->with(self::STORE_ID)
            ->willReturn($centralVersion);
    }

    public function testUseSalesPriceNoVersion25RoutesToSalesPrice(): void
    {
        $this->configure('0', '25.0.0.0');

        $this->salesPrice->expects($this->once())->method('setOperationInput');

        $result = $this->plugin->aroundMakeRequest($this->subject, $this->failingProceed());

        $this->assertSame($this->salesPrice, $result, 'version < 26 must route to SalesPrice.');
    }

    public function testUseSalesPriceNoVersion26RoutesToPriceListLine(): void
    {
        $this->configure('0', '26.0.0.0');

        $this->priceListLine->expects($this->once())->method('setOperationInput');

        $result = $this->plugin->aroundMakeRequest($this->subject, $this->failingProceed());

        $this->assertSame($this->priceListLine, $result, 'version 26 must route to PriceListLine.');
    }

    public function testUseSalesPriceNoVersion27RoutesToPriceListLine(): void
    {
        $this->configure('0', '27.9.9.9');

        $this->priceListLine->expects($this->once())->method('setOperationInput');

        $result = $this->plugin->aroundMakeRequest($this->subject, $this->failingProceed());

        $this->assertSame($this->priceListLine, $result, 'version in [26, 28) must route to PriceListLine.');
    }

    public function testUseSalesPriceNoVersion28CallsProceed(): void
    {
        $this->configure('0', '28.0.0.0');

        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
            return 'PROCEED_RESULT';
        };

        $result = $this->plugin->aroundMakeRequest($this->subject, $proceed);

        $this->assertTrue($proceedCalled, 'version >= 28 must call proceed (SalePriceView).');
        $this->assertSame('PROCEED_RESULT', $result);
    }

    public function testUseSalesPriceYesVersion30RoutesToSalesPrice(): void
    {
        $this->configure('1', '30.0.0.0');

        $this->salesPrice->expects($this->once())->method('setOperationInput');

        $result = $this->plugin->aroundMakeRequest($this->subject, $this->failingProceed());

        $this->assertSame(
            $this->salesPrice,
            $result,
            'UseSalesPrice=Yes must route to SalesPrice regardless of version.'
        );
    }

    /**
     * A proceed callback that fails the test if invoked (used for non-proceed routes).
     *
     * @return callable
     */
    private function failingProceed(): callable
    {
        return function () {
            $this->fail('$proceed must NOT be called for this route.');
        };
    }
}
