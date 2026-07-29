<?php

declare(strict_types=1);

namespace Ls\Core\Test\Unit\Model;

use Ls\Core\Model\Data;
use Ls\Core\Model\LSR;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see LSR::isAdminOrderCustomPriceActive()}.
 *
 * Ticket 85191: the getter must be genuinely store-view scoped - i.e. it must delegate to the
 * existing store-scoped {@see LSR::getStoreConfig()} helper (which reads
 * ScopeInterface::SCOPE_STORES when a store id is given, and falls back to the default scope
 * otherwise), NOT the non-scoped `$this->scopeConfig->getValue($path)` pattern used by some
 * older getters in this class (e.g. getOrderIntegrationOnFrontend()), which always ignores
 * store-view overrides.
 *
 * LSR has a small, fully-promoted constructor (scopeConfig, storeManager, data) so it is safe
 * to instantiate normally with mocks here.
 */
class LSRTest extends TestCase
{
    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private $scopeConfig;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $data = $this->createMock(Data::class);

        $this->lsr = new LSR($this->scopeConfig, $storeManager, $data);
    }

    /**
     * When a store id is provided, the config must be read from the store scope
     * (ScopeInterface::SCOPE_STORES) for that exact store - mirroring getStoreConfig()'s
     * behaviour - not the ambient/current scope.
     */
    public function testIsAdminOrderCustomPriceActiveIsStoreScopedWhenStoreIdProvided(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(LSR::LS_ADMIN_ORDER_CUSTOM_PRICE_ACTIVE, ScopeInterface::SCOPE_STORES, 7)
            ->willReturn('1');

        $this->assertTrue($this->lsr->isAdminOrderCustomPriceActive(7));
    }

    /**
     * When no store id is provided (default/false), getStoreConfig() falls back to reading
     * the value with no explicit scope arguments at all - the getter must preserve this exact
     * delegation rather than hardcoding SCOPE_TYPE_DEFAULT itself.
     */
    public function testIsAdminOrderCustomPriceActiveDefaultsToNoExplicitScopeWhenStoreIdOmitted(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(LSR::LS_ADMIN_ORDER_CUSTOM_PRICE_ACTIVE)
            ->willReturn(0);

        $this->assertFalse($this->lsr->isAdminOrderCustomPriceActive());
    }

    /**
     * Explicitly passing false (the getter's own default value) must behave identically to
     * omitting the argument.
     */
    public function testIsAdminOrderCustomPriceActiveDefaultsToNoExplicitScopeWhenStoreIdFalse(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(LSR::LS_ADMIN_ORDER_CUSTOM_PRICE_ACTIVE)
            ->willReturn('0');

        $this->assertFalse($this->lsr->isAdminOrderCustomPriceActive(false));
    }
}
