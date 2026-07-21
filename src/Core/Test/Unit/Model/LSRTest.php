<?php

namespace Ls\Core\Test\Unit\Model;

use Ls\Core\Model\LSR;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test coverage for LSR::isUseSalesPriceEnabled() and LSR::isAdminOrderCustomPriceActive().
 *
 * TDD note: LSR::isUseSalesPriceEnabled() and the SC_REPLICATION_USE_SALES_PRICE constant do not
 * exist yet. These tests are expected to FAIL until the production code from
 * solution-plan-84363-use-sales-price-config.md is implemented.
 *
 * LSR::isAdminOrderCustomPriceActive() and the LS_ADMIN_ORDER_CUSTOM_PRICE_ACTIVE constant also do
 * not exist yet (work item #85191, solution-plan-85191-custom-order-price.md §3.2). Per that plan,
 * the getter must delegate to the store-scoped `getStoreConfig($path, $storeId)` helper — not the
 * non-scoped `$this->scopeConfig->getValue($path)` (no scope args) pattern used by the existing
 * `getOrderIntegrationOnFrontend(false)` branch above. The tests below assert the exact
 * `scopeConfig->getValue()` call shape (args) `getStoreConfig()` produces, so that an implementation
 * that "corrects" the getter back to the non-scoped pattern would fail these tests.
 */
class LSRTest extends TestCase
{
    private const SC_REPLICATION_USE_SALES_PRICE = 'ls_mag/replication/use_sales_price';

    private const LS_ADMIN_ORDER_CUSTOM_PRICE_ACTIVE = 'ls_mag/standalone_integration/admin_order_custom_price';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->lsr = $this->objectManager->getObject(LSR::class);
        // scopeConfig is a public property on LSR (`public $scopeConfig;`); inject the mock directly.
        $this->lsr->scopeConfig = $this->scopeConfig;
    }

    public function testConstantValue(): void
    {
        $this->assertSame(
            self::SC_REPLICATION_USE_SALES_PRICE,
            LSR::SC_REPLICATION_USE_SALES_PRICE
        );
    }

    public function testReturnsScopedFlagWhenScopeIdProvided(): void
    {
        $scopeId = 3;

        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(
                self::SC_REPLICATION_USE_SALES_PRICE,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeId
            )
            ->willReturn(true);

        $this->assertTrue($this->lsr->isUseSalesPriceEnabled($scopeId));
    }

    public function testReturnsDefaultFlagWhenNoScopeId(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(self::SC_REPLICATION_USE_SALES_PRICE)
            ->willReturn(false);

        $this->assertFalse($this->lsr->isUseSalesPriceEnabled());
    }

    public function testAdminOrderCustomPriceConstantValue(): void
    {
        $this->assertSame(
            self::LS_ADMIN_ORDER_CUSTOM_PRICE_ACTIVE,
            LSR::LS_ADMIN_ORDER_CUSTOM_PRICE_ACTIVE
        );
    }

    /**
     * When a store ID is supplied, isAdminOrderCustomPriceActive() must read the store-scoped
     * value (mirrors getStoreConfig()'s `$storeId` branch: scopeConfig->getValue($path,
     * ScopeInterface::SCOPE_STORES, $storeId)) — genuinely store-view scoped, per solution plan §3.2
     * and §2.10 of the evidence section (deliberately deviating from the non-scoped
     * getOrderIntegrationOnFrontend() shape).
     */
    public function testReturnsStoreScopedValueWhenStoreIdProvided(): void
    {
        $storeId = 7;

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                self::LS_ADMIN_ORDER_CUSTOM_PRICE_ACTIVE,
                ScopeInterface::SCOPE_STORES,
                $storeId
            )
            ->willReturn('1');

        $this->assertTrue($this->lsr->isAdminOrderCustomPriceActive($storeId));
    }

    /**
     * When no store ID is supplied (default `false`), isAdminOrderCustomPriceActive() must fall
     * through to getStoreConfig()'s no-store branch: scopeConfig->getValue($path) with no scope
     * arguments at all.
     */
    public function testReturnsDefaultScopeValueWhenNoStoreIdProvided(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(self::LS_ADMIN_ORDER_CUSTOM_PRICE_ACTIVE)
            ->willReturn(0);

        $this->assertFalse($this->lsr->isAdminOrderCustomPriceActive());
    }
}