<?php

namespace Ls\Replication\Test\Unit\Helper;

use Ls\Core\Model\LSR;
use Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test coverage for ReplicationHelper::getUniqueFieldArray().
 *
 * TDD note: getUniqueFieldArray() and LSR::isUseSalesPriceEnabled() do not exist yet.
 * These tests are expected to FAIL until the production code from
 * solution-plan-84363-use-sales-price-config.md is implemented.
 */
class ReplicationHelperTest extends TestCase
{
    private const REPL_PRICE_CONFIG_PATH = 'ls_mag/replication/repl_price';
    private const REPL_BARCODE_CONFIG_PATH = 'ls_mag/replication/repl_barcode';
    private const REPL_ITEM_VARIANT_REGISTRATION_CONFIG_PATH = 'ls_mag/replication/repl_item_variant_registration';

    /**
     * Legacy pre-#82767 sales-price unique key (matches PRICE_SALES_UNIQUE_FIELD_ARRAY in the plan).
     */
    private const SALES_PRICE_UNIQUE_FIELD_ARRAY = [
        'ItemId',
        'VariantId',
        'StoreId',
        'QtyPerUnitOfMeasure',
        'UnitOfMeasure',
        'PriceListCode',
        'scope_id',
    ];

    /**
     * Current JOB_CODE_UNIQUE_FIELD_ARRAY value for repl_price (post-#82767).
     */
    private const CURRENT_PRICE_UNIQUE_FIELD_ARRAY = [
        'LineNumber',
        'StoreId',
        'PriceListCode',
        'scope_id',
    ];

    /**
     * Current JOB_CODE_UNIQUE_FIELD_ARRAY value for repl_barcode.
     */
    private const BARCODE_UNIQUE_FIELD_ARRAY = [
        'nav_id',
        'scope_id',
    ];

    /**
     * Current DELETE_JOB_CODE_UNIQUE_FIELD_ARRAY value for repl_item_variant_registration.
     */
    private const ITEM_VARIANT_REGISTRATION_DELETE_UNIQUE_FIELD_ARRAY = [
        'ItemId',
        'VariantDimension1',
        'VariantDimension2',
        'VariantDimension3',
        'VariantDimension4',
        'VariantDimension5',
        'VariantDimension6',
    ];

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ReplicationHelper
     */
    private $helper;

    /**
     * @var LSR|MockObject
     */
    private $lsr;

    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->lsr = $this->createMock(LSR::class);
        $this->helper = $this->objectManager->getObject(ReplicationHelper::class);
        // $lsr is a public property on ReplicationHelper (see `public $lsr;`), so we inject the mock
        // directly and control isUseSalesPriceEnabled() without wiring the full constructor.
        $this->helper->lsr = $this->lsr;
    }

    public function testReplPriceReturnsSalesPriceArrayWhenUseSalesPriceEnabled(): void
    {
        $this->lsr->expects($this->once())
            ->method('isUseSalesPriceEnabled')
            ->with(false, ScopeInterface::SCOPE_WEBSITES)
            ->willReturn(true);

        $result = $this->helper->getUniqueFieldArray(
            self::REPL_PRICE_CONFIG_PATH,
            false,
            ScopeInterface::SCOPE_WEBSITES,
            false
        );

        $this->assertSame(self::SALES_PRICE_UNIQUE_FIELD_ARRAY, $result);
    }

    public function testReplPriceReturnsCurrentArrayWhenUseSalesPriceDisabled(): void
    {
        $this->lsr->expects($this->once())
            ->method('isUseSalesPriceEnabled')
            ->with(false, ScopeInterface::SCOPE_WEBSITES)
            ->willReturn(false);

        $result = $this->helper->getUniqueFieldArray(
            self::REPL_PRICE_CONFIG_PATH,
            false,
            ScopeInterface::SCOPE_WEBSITES,
            false
        );

        $this->assertSame(self::CURRENT_PRICE_UNIQUE_FIELD_ARRAY, $result);
    }

    public function testReplPriceSalesPriceOverrideTakesPrecedenceOverDeleteBranch(): void
    {
        $this->lsr->expects($this->once())
            ->method('isUseSalesPriceEnabled')
            ->with(false, ScopeInterface::SCOPE_WEBSITES)
            ->willReturn(true);

        // repl_price is absent from DELETE_JOB_CODE_UNIQUE_FIELD_ARRAY, so even with isDeleted=true
        // the sales-price override must win.
        $result = $this->helper->getUniqueFieldArray(
            self::REPL_PRICE_CONFIG_PATH,
            true,
            ScopeInterface::SCOPE_WEBSITES,
            false
        );

        $this->assertSame(self::SALES_PRICE_UNIQUE_FIELD_ARRAY, $result);
    }

    public function testOtherJobCodeUnaffectedWhenUseSalesPriceEnabled(): void
    {
        // A non-repl_price code must never consult the sales-price config nor return that array.
        $this->lsr->expects($this->never())
            ->method('isUseSalesPriceEnabled');

        $result = $this->helper->getUniqueFieldArray(
            self::REPL_BARCODE_CONFIG_PATH,
            false,
            ScopeInterface::SCOPE_WEBSITES,
            false
        );

        $this->assertSame(self::BARCODE_UNIQUE_FIELD_ARRAY, $result);
    }

    public function testDeleteJobCodeReturnsDeleteArrayUnaffectedBySalesPriceConfig(): void
    {
        // A code present in DELETE_JOB_CODE_UNIQUE_FIELD_ARRAY with isDeleted=true returns the
        // delete array; the sales-price config is irrelevant for non-repl_price codes.
        $this->lsr->expects($this->never())
            ->method('isUseSalesPriceEnabled');

        $result = $this->helper->getUniqueFieldArray(
            self::REPL_ITEM_VARIANT_REGISTRATION_CONFIG_PATH,
            true,
            ScopeInterface::SCOPE_WEBSITES,
            false
        );

        $this->assertSame(self::ITEM_VARIANT_REGISTRATION_DELETE_UNIQUE_FIELD_ARRAY, $result);
    }

    /**
     * When a scope id is supplied, getUomCodeGivenDescription() must add a scope_id filter to the
     * reverse lookup (mirroring getUomDescriptionGivenCodeAndScopeId) so that in a multi-website
     * setup the correct scope's UOM code is resolved. Captures the filters handed to
     * searchCriteriaBuilder::addFilter() and asserts both the description and scope_id are present.
     */
    public function testGetUomCodeGivenDescriptionAddsScopeIdFilterWhenScopeProvided(): void
    {
        $capturedFilters = [];
        $searchCriteriaBuilder = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['setFilterGroups', 'addFilter', 'setPageSize', 'create'])
            ->getMock();
        $searchCriteriaBuilder->method('setFilterGroups')->willReturnSelf();
        $searchCriteriaBuilder->method('addFilter')
            ->willReturnCallback(function ($field, $value, $condition) use (&$capturedFilters, $searchCriteriaBuilder) {
                $capturedFilters[$field] = $value;
                return $searchCriteriaBuilder;
            });
        $searchCriteriaBuilder->method('setPageSize')->willReturnSelf();
        $searchCriteriaBuilder->method('create')->willReturn(
            $this->createMock(\Magento\Framework\Api\SearchCriteriaInterface::class)
        );

        $uom = $this->getMockBuilder(\stdClass::class)->addMethods(['getNavId'])->getMock();
        $uom->method('getNavId')->willReturn('PCS');
        $results = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getTotalCount', 'getItems'])
            ->getMock();
        $results->method('getTotalCount')->willReturn(1);
        $results->method('getItems')->willReturn([$uom]);

        $repository = $this->getMockBuilder(\stdClass::class)->addMethods(['getList'])->getMock();
        $repository->method('getList')->willReturn($results);

        $this->helper->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper->replUnitOfMeasureRepository = $repository;

        $code = $this->helper->getUomCodeGivenDescription('Pieces', 1);

        $this->assertSame('PCS', $code);
        $this->assertArrayHasKey('description', $capturedFilters);
        $this->assertSame('Pieces', $capturedFilters['description']);
        $this->assertArrayHasKey('scope_id', $capturedFilters);
        $this->assertSame(1, $capturedFilters['scope_id']);
    }

    /**
     * Backward compatibility: with no scope id the reverse lookup filters by description only,
     * never emitting a scope_id filter (the parameter stays optional/nullable).
     */
    public function testGetUomCodeGivenDescriptionOmitsScopeIdFilterWhenNoScope(): void
    {
        $capturedFilters = [];
        $searchCriteriaBuilder = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['setFilterGroups', 'addFilter', 'setPageSize', 'create'])
            ->getMock();
        $searchCriteriaBuilder->method('setFilterGroups')->willReturnSelf();
        $searchCriteriaBuilder->method('addFilter')
            ->willReturnCallback(function ($field, $value, $condition) use (&$capturedFilters, $searchCriteriaBuilder) {
                $capturedFilters[$field] = $value;
                return $searchCriteriaBuilder;
            });
        $searchCriteriaBuilder->method('setPageSize')->willReturnSelf();
        $searchCriteriaBuilder->method('create')->willReturn(
            $this->createMock(\Magento\Framework\Api\SearchCriteriaInterface::class)
        );

        $results = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getTotalCount', 'getItems'])
            ->getMock();
        $results->method('getTotalCount')->willReturn(0);
        $results->method('getItems')->willReturn([]);

        $repository = $this->getMockBuilder(\stdClass::class)->addMethods(['getList'])->getMock();
        $repository->method('getList')->willReturn($results);

        $this->helper->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper->replUnitOfMeasureRepository = $repository;

        $code = $this->helper->getUomCodeGivenDescription('Pieces');

        $this->assertSame('', $code);
        $this->assertArrayHasKey('description', $capturedFilters);
        $this->assertArrayNotHasKey('scope_id', $capturedFilters);
    }
}