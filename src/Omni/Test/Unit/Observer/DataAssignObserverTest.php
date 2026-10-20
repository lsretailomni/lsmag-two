<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Observer;

use Ls\Omni\Helper\Data;
use Ls\Omni\Helper\VendorSourcingHelper;
use Ls\Omni\Model\VendorSourcing\CheckoutFallbackResult;
use Ls\Omni\Model\VendorSourcing\CheckoutFallbackService;
use Ls\Omni\Model\VendorSourcing\ItemSourcingVerdict;
use Ls\Omni\Observer\DataAssignObserver;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see DataAssignObserver::storeInventoryCheck()} and the new
 * {@see DataAssignObserver::validateDeliveryOrder()} (ticket 89360, solution plan §5.3/§5.4/§12).
 *
 * DataAssignObserver's dependencies are all `public`-promoted constructor properties (the file's
 * own existing convention - not `private readonly`), so following the BasketHelperTest precedent
 * of assigning mocked collaborators directly onto a constructor-disabled instance, the observer
 * under test is built via getMockBuilder(...)->disableOriginalConstructor()->onlyMethods([])
 * (mocking none of its own methods - storeInventoryCheck()/validateDeliveryOrder() run for
 * real) with only the collaborators each test needs assigned directly.
 *
 * `checkoutFallbackService` is a dependency this ticket added (solution plan §5.1) - it is now a
 * normal, typed, directly-settable public property on the class, same as `helper`/`checkoutSession`.
 *
 * `buildVendorSourcedLinesMap()` (and its tests) have been REMOVED from this class entirely (bug
 * fix, post-merge review, Bug B): it has been relocated/rehomed to
 * {@see CheckoutFallbackService::buildVendorSourcedLinesMap()} (tested in
 * CheckoutFallbackServiceTest), since OrderHelper::prepareOrder() now also needs it, directly off
 * a freshly-loaded quote's items, rather than off the transient Order-level
 * 'ls_vendor_sourced_lines' data this class used to stamp onto $order in execute() (which did not
 * survive a reloaded Order on a separate HTTP request for some payment methods).
 */
class DataAssignObserverTest extends TestCase
{
    /** @var DataAssignObserver&MockObject */
    private $observer;

    /** @var Data&MockObject */
    private $helper;

    /** @var CheckoutSession&MockObject */
    private $checkoutSession;

    /** @var CheckoutFallbackService&MockObject */
    private $checkoutFallbackService;

    protected function setUp(): void
    {
        $this->observer = $this->getMockBuilder(DataAssignObserver::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->helper = $this->createMock(Data::class);
        // getNoManageStock() is served via Magento\Framework\Session\SessionManager::__call()
        // (no real declared method) - PHPUnit 10's createMock() can only configure genuinely
        // declared methods, so addMethods() is required here to stub this magic accessor.
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['getNoManageStock'])
            ->getMock();
        $this->checkoutFallbackService = $this->createMock(CheckoutFallbackService::class);

        $this->observer->helper = $this->helper;
        $this->observer->checkoutSession = $this->checkoutSession;
        $this->observer->checkoutFallbackService = $this->checkoutFallbackService;
    }

    /**
     * @param bool $blocked
     * @param ItemSourcingVerdict[] $blockedVerdicts
     * @return CheckoutFallbackResult&MockObject
     */
    private function createFallbackResult(bool $blocked, array $blockedVerdicts = []): CheckoutFallbackResult
    {
        $result = $this->createMock(CheckoutFallbackResult::class);
        $result->method('hasBlockedItems')->willReturn($blocked);
        $result->method('getBlockedVerdicts')->willReturn($blockedVerdicts);

        return $result;
    }

    private function createBlockedVerdict(string $sku, string $message): ItemSourcingVerdict
    {
        $verdict = $this->createMock(ItemSourcingVerdict::class);
        $verdict->method('getSku')->willReturn($sku);
        $verdict->method('getMessage')->willReturn(__($message));

        return $verdict;
    }

    // ------------------------------------------------------ storeInventoryCheck() ----

    /**
     * Only a genuine `null` from fetchCartAndReturnStock() (lookup truly could not be performed)
     * is a failure (bug fix, post-merge review, Bug A) - see
     * testStoreInventoryCheckReturnsNullWhenStockCollectionIsEmptyArray() for the companion
     * "empty array is NOT a failure" case this fix introduces.
     */
    public function testStoreInventoryCheckReturnsOopsMessageWhenStockCollectionIsNull(): void
    {
        $this->helper->method('fetchCartAndReturnStock')->willReturn(null);
        $this->checkoutSession->method('getNoManageStock')->willReturn(null);

        $this->checkoutFallbackService->expects($this->never())->method('evaluate');

        $result = $this->observer->storeInventoryCheck('masked-cart-id', 1, 1, 'STORE01');

        $this->assertSame('Oops! Unable to do stock lookup currently.', (string)$result);
    }

    /**
     * Regression test for the bug fix (post-merge review, Bug A): an EMPTY array (as opposed to
     * null) means there was simply nothing left needing a per-store check - e.g. a cart that is
     * entirely vendor-sourced/unmanaged items once ticket 89358's manage_stock=0 gating lands -
     * and must proceed (return null), not surface the "Oops!" failure message. No $quote is passed
     * here, so evaluate() must not be called either (matches the GraphQL call path today, which
     * also omits $quote).
     */
    public function testStoreInventoryCheckReturnsNullWhenStockCollectionIsEmptyArray(): void
    {
        $this->helper->method('fetchCartAndReturnStock')->willReturn([]);
        $this->checkoutSession->method('getNoManageStock')->willReturn(null);

        $this->checkoutFallbackService->expects($this->never())->method('evaluate');

        $result = $this->observer->storeInventoryCheck('masked-cart-id', 1, 1, 'STORE01');

        $this->assertNull($result);
    }

    public function testStoreInventoryCheckReturnsNullWhenAllItemsInStock(): void
    {
        $this->helper->method('fetchCartAndReturnStock')->willReturn([
            ['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'status' => '1'],
        ]);
        $this->checkoutSession->method('getNoManageStock')->willReturn(null);

        $this->checkoutFallbackService->expects($this->never())->method('evaluate');

        $result = $this->observer->storeInventoryCheck('masked-cart-id', 1, 1, 'STORE01');

        $this->assertNull($result);
    }

    public function testStoreInventoryCheckDelegatesToCheckoutFallbackServiceEvaluateWithTypeCollectWhenZeroStock(): void
    {
        $stockCollection = [
            ['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'status' => '0'],
        ];
        $this->helper->method('fetchCartAndReturnStock')->willReturn($stockCollection);
        $this->checkoutSession->method('getNoManageStock')->willReturn(null);

        $this->checkoutFallbackService->expects($this->once())
            ->method('evaluate')
            ->with($stockCollection, VendorSourcingHelper::TYPE_COLLECT, [])
            ->willReturn($this->createFallbackResult(false));

        $result = $this->observer->storeInventoryCheck('masked-cart-id', 1, 1, 'STORE01');

        $this->assertNull($result);
    }

    public function testStoreInventoryCheckReturnsBlockedMessageWhenFallbackResultHasBlockedItems(): void
    {
        $stockCollection = [
            ['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'status' => '0'],
        ];
        $this->helper->method('fetchCartAndReturnStock')->willReturn($stockCollection);
        $this->checkoutSession->method('getNoManageStock')->willReturn(null);

        $blockedVerdict = $this->createBlockedVerdict(
            'SKU-1',
            'Product A (SKU: SKU-1) is not available at the selected store and cannot be sourced from a vendor.'
        );
        $this->checkoutFallbackService->method('evaluate')
            ->willReturn($this->createFallbackResult(true, [$blockedVerdict]));

        $result = $this->observer->storeInventoryCheck('masked-cart-id', 1, 1, 'STORE01');

        $this->assertStringContainsString('SKU-1', (string)$result);
        $this->assertStringContainsString('cannot be sourced from a vendor', (string)$result);
    }

    /**
     * Regression test for the bug fix (post-merge review, Bug A, "C&C trigger gap"): when
     * $stockCollection has NO zero-stock row at all (e.g. because every vendor-sourced item was
     * already excluded from it by StockHelper::getGivenItemsStockInGivenStore()'s manage_stock
     * gate), evaluate() must still be called - with the quote's items - as long as a $quote was
     * passed through, so such items get a chance to be independently re-evaluated.
     */
    public function testStoreInventoryCheckCallsEvaluateWithQuoteItemsEvenWithNoZeroStockRowWhenQuotePassed(): void
    {
        $stockCollection = [
            ['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'status' => '1'],
        ];
        $this->helper->method('fetchCartAndReturnStock')->willReturn($stockCollection);
        $this->checkoutSession->method('getNoManageStock')->willReturn(null);

        $excludedItem = $this->createMock(Item::class);
        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$excludedItem]);

        $this->checkoutFallbackService->expects($this->once())
            ->method('evaluate')
            ->with($stockCollection, VendorSourcingHelper::TYPE_COLLECT, [$excludedItem])
            ->willReturn($this->createFallbackResult(false));

        $result = $this->observer->storeInventoryCheck('masked-cart-id', 1, 1, 'STORE01', $quote);

        $this->assertNull($result);
    }

    /**
     * Companion no-op regression: an all-in-stock cart with no $quote passed at all (the GraphQL
     * call path, which omits $quote today) must not call evaluate() - there is nothing to
     * independently re-discover without quote items available.
     */
    public function testStoreInventoryCheckDoesNotCallEvaluateWhenNoZeroStockRowAndNoQuotePassed(): void
    {
        $stockCollection = [
            ['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'status' => '1'],
        ];
        $this->helper->method('fetchCartAndReturnStock')->willReturn($stockCollection);
        $this->checkoutSession->method('getNoManageStock')->willReturn(null);

        $this->checkoutFallbackService->expects($this->never())->method('evaluate');

        $result = $this->observer->storeInventoryCheck('masked-cart-id', 1, 1, 'STORE01');

        $this->assertNull($result);
    }

    // ------------------------------------------------------ validateDeliveryOrder() ----

    public function testValidateDeliveryOrderDelegatesToEvaluateDeliveryAndReturnsNullWhenNotBlocked(): void
    {
        $item = $this->createMock(Item::class);
        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $this->checkoutFallbackService->expects($this->once())
            ->method('evaluateDelivery')
            ->with([$item])
            ->willReturn($this->createFallbackResult(false));

        $result = $this->observer->validateDeliveryOrder($quote);

        $this->assertNull($result);
    }

    public function testValidateDeliveryOrderReturnsBlockedMessageWhenEvaluateDeliveryResultHasBlockedItems(): void
    {
        $item = $this->createMock(Item::class);
        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $blockedVerdict = $this->createBlockedVerdict(
            'SKU-2',
            'Product B (SKU: SKU-2) is not available and cannot be sourced from a vendor for delivery.'
        );
        $this->checkoutFallbackService->method('evaluateDelivery')
            ->willReturn($this->createFallbackResult(true, [$blockedVerdict]));

        $result = $this->observer->validateDeliveryOrder($quote);

        $this->assertStringContainsString('SKU-2', (string)$result);
        $this->assertStringContainsString('for delivery', (string)$result);
    }
}
