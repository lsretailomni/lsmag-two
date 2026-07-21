<?php

namespace Ls\Core\Test\Unit\Model;

use Ls\Core\Model\LSR;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test coverage for LSR::isUseSalesPriceEnabled().
 *
 * TDD note: LSR::isUseSalesPriceEnabled() and the SC_REPLICATION_USE_SALES_PRICE constant do not
 * exist yet. These tests are expected to FAIL until the production code from
 * solution-plan-84363-use-sales-price-config.md is implemented.
 */
class LSRTest extends TestCase
{
    private const SC_REPLICATION_USE_SALES_PRICE = 'ls_mag/replication/use_sales_price';

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
}