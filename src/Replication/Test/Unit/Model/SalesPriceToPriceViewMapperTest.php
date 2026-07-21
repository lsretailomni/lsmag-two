<?php

declare(strict_types=1);

namespace Ls\Replication\Test\Unit\Model;

use Ls\Omni\Client\CentralEcommerce\Entity\SalePriceView;
use Ls\Omni\Client\CentralEcommerce\Entity\SalesPrice;
use Ls\Replication\Model\SalesPriceToPriceViewMapper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SalesPriceToPriceViewMapper.
 */
class SalesPriceToPriceViewMapperTest extends TestCase
{
    private const SIGNED_INT_MAX = 0x7FFFFFFF;

    /**
     * @var SalesPriceToPriceViewMapper
     */
    private $mapper;

    protected function setUp(): void
    {
        $this->mapper = new SalesPriceToPriceViewMapper();
    }

    /**
     * Build a SalesPrice mock whose getters return the given values and whose setData()
     * calls are captured into the returned $captured array (by reference).
     *
     * @param array $values
     * @param array $captured
     * @return SalesPrice&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSource(array $values, array &$captured)
    {
        $defaults = [
            'ItemNo'                   => 'ITEM001',
            'VariantCode'              => 'V1',
            'UnitOfMeasureCode'        => 'PCS',
            'CurrencyCode'             => 'USD',
            'StartingDate'             => '2026-01-01T00:00:00',
            'EndingDate'               => '2026-12-31T00:00:00',
            'MinimumQuantity'          => 1.0,
            'UnitPrice'                => 10.0,
            'LscUnitPriceIncludingVat' => 12.0,
            'PriceIncludesVat'         => true,
            'VatBusPostingGrPrice'     => 'DOMESTIC',
            'AllowInvoiceDisc'         => false,
            'AllowLineDisc'            => false,
            'SalesCode'                => '',
            'SalesType'                => 1,
        ];
        $values += $defaults;

        $source = $this->createMock(SalesPrice::class);
        $source->method('getItemNo')->willReturn($values['ItemNo']);
        $source->method('getVariantCode')->willReturn($values['VariantCode']);
        $source->method('getUnitOfMeasureCode')->willReturn($values['UnitOfMeasureCode']);
        $source->method('getCurrencyCode')->willReturn($values['CurrencyCode']);
        $source->method('getStartingDate')->willReturn($values['StartingDate']);
        $source->method('getEndingDate')->willReturn($values['EndingDate']);
        $source->method('getMinimumQuantity')->willReturn($values['MinimumQuantity']);
        $source->method('getUnitPrice')->willReturn($values['UnitPrice']);
        $source->method('getLscUnitPriceIncludingVat')->willReturn($values['LscUnitPriceIncludingVat']);
        $source->method('getPriceIncludesVat')->willReturn($values['PriceIncludesVat']);
        $source->method('getVatBusPostingGrPrice')->willReturn($values['VatBusPostingGrPrice']);
        $source->method('getAllowInvoiceDisc')->willReturn($values['AllowInvoiceDisc']);
        $source->method('getAllowLineDisc')->willReturn($values['AllowLineDisc']);
        $source->method('getSalesCode')->willReturn($values['SalesCode']);
        $source->method('getSalesType')->willReturn($values['SalesType']);

        $source->method('setData')->willReturnCallback(
            function ($key, $value = null) use (&$captured, $source) {
                $captured[$key] = $value;
                return $source;
            }
        );

        return $source;
    }

    /**
     * Reshape a source built from the given override values and return the captured setData map.
     *
     * @param array $values
     * @return array
     */
    private function reshapeAndCapture(array $values): array
    {
        $captured = [];
        $source = $this->createSource($values, $captured);
        $this->mapper->reshape($source);

        return $captured;
    }

    public function testSourceNoEqualsSalesCodeAndAssetNoEqualsItemNo(): void
    {
        $captured = $this->reshapeAndCapture([
            'ItemNo'    => 'ITEM999',
            'SalesCode' => 'GROUP_A',
        ]);

        $this->assertSame('ITEM999', $captured[SalePriceView::ASSET_NO], 'AssetNo must be the item number.');
        $this->assertSame('GROUP_A', $captured[SalePriceView::SOURCE_NO], 'SourceNo must be the sales code.');
        $this->assertSame('GROUP_A', $captured[SalePriceView::PRICE_LIST_CODE], 'PriceListCode must be the sales code.');
    }

    public function testStatusIsAlwaysActive(): void
    {
        $captured = $this->reshapeAndCapture([]);

        $this->assertSame(1, $captured[SalePriceView::STATUS], 'Reshaped SalesPrice must be forced Active (Status=1).');
    }

    public function testSynthesizedLineNoIsWithinSignedIntRange(): void
    {
        // Representative inputs, including keys whose raw crc32 exceeds the signed-INT max.
        $inputs = [
            ['ItemNo' => 'A'],
            ['ItemNo' => 'ITEM12345', 'VariantCode' => 'VAR-XYZ', 'CurrencyCode' => 'EUR'],
            ['ItemNo' => 'ZZZZZZZZ', 'SalesCode' => 'HIGHCRC', 'StartingDate' => '2030-06-15T00:00:00'],
            ['ItemNo' => str_repeat('Q', 64)],
        ];

        $sawAboveSignedMax = false;
        foreach ($inputs as $input) {
            $captured = $this->reshapeAndCapture($input);
            $lineNo   = $captured[SalePriceView::LINE_NO];

            $this->assertIsInt($lineNo);
            $this->assertGreaterThanOrEqual(0, $lineNo, 'LineNo must be non-negative.');
            $this->assertLessThanOrEqual(
                self::SIGNED_INT_MAX,
                $lineNo,
                'LineNo must fit the signed-INT LineNumber column.'
            );
        }

        // Explicit check: find an input whose raw crc32 exceeds the signed-INT max.
        foreach (['A', 'ITEM12345', 'ZZZZZZZZ', str_repeat('Q', 64), 'overflow-seed-123'] as $seed) {
            // phpcs:ignore Magento2.Security.InsecureFunction
            if (crc32($seed) > self::SIGNED_INT_MAX) {
                $sawAboveSignedMax = true;
                break;
            }
        }
        $this->assertTrue(
            $sawAboveSignedMax,
            'Test fixture must include at least one key whose raw crc32 exceeds 2^31-1 (mask coverage).'
        );
    }

    public function testSynthesizedLineNoIsDeterministic(): void
    {
        $values = [
            'ItemNo'          => 'ITEM777',
            'VariantCode'     => 'VB',
            'CurrencyCode'    => 'GBP',
            'StartingDate'    => '2026-03-01T00:00:00',
            'MinimumQuantity' => 5.0,
        ];

        $first  = $this->reshapeAndCapture($values)[SalePriceView::LINE_NO];
        $second = $this->reshapeAndCapture($values)[SalePriceView::LINE_NO];

        $this->assertSame($first, $second, 'Same input must yield the same LineNo.');
    }

    public function testLineNoDiffersWhenOnlyMinimumQuantityDiffers(): void
    {
        $base = ['ItemNo' => 'ITEM555', 'MinimumQuantity' => 1.0];
        $qty  = ['ItemNo' => 'ITEM555', 'MinimumQuantity' => 10.0];

        $lineNoBase = $this->reshapeAndCapture($base)[SalePriceView::LINE_NO];
        $lineNoQty  = $this->reshapeAndCapture($qty)[SalePriceView::LINE_NO];

        $this->assertNotSame(
            $lineNoBase,
            $lineNoQty,
            'Quantity-break lines must hash to distinct LineNo values.'
        );
    }

    public function testLineNoDiffersWhenOnlySalesCodeDiffers(): void
    {
        $base  = ['ItemNo' => 'ITEM555', 'SalesCode' => ''];
        $group = ['ItemNo' => 'ITEM555', 'SalesCode' => 'GROUP_B'];

        $lineNoBase  = $this->reshapeAndCapture($base)[SalePriceView::LINE_NO];
        $lineNoGroup = $this->reshapeAndCapture($group)[SalePriceView::LINE_NO];

        $this->assertNotSame(
            $lineNoBase,
            $lineNoGroup,
            'Rows differing only by SalesCode must hash to distinct LineNo values.'
        );
    }
}
