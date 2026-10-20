<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Helper;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\CentralEcommerce\Entity\CustomerOrderCreateCOLineV6;
use Ls\Omni\Client\CentralEcommerce\Entity\MobileTransaction;
use Ls\Omni\Client\CentralEcommerce\Entity\MobileTransactionLine;
use Ls\Omni\Client\CentralEcommerce\Entity\RootCustomerOrderCreateV6;
use Ls\Omni\Client\CentralEcommerce\Entity\RootMobileTransaction;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\LoyaltyHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\Omni\Helper\StockHelper;
use Ls\Omni\Model\VendorSourcing\CheckoutFallbackService;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for {@see OrderHelper::prepareOrder()}'s vendor-sourcing order-submission behaviour
 * (ticket 89364, follow-on to ticket 89360's checkout fallback).
 *
 * Bug B fix (post-merge review): prepareOrder() no longer reads a transient
 * $order->getData('ls_vendor_sourced_lines') value - that approach did not survive a reloaded
 * Order on a separate HTTP request for some payment methods (checkout_onepage_controller_success_
 * action/adyen_payment_process_resulturl_after). It now recomputes the determination FRESH off a
 * quote loaded via $this->quoteRepository->get($order->getQuoteId()) every time it runs, re-running
 * CheckoutFallbackService::evaluate()/evaluateDelivery() against that freshly-loaded quote's items,
 * then builds the "item_id|variant_id" -> vendor-sourcing-info map via
 * CheckoutFallbackService::buildVendorSourcedLinesMap(). None of these tests ever stub
 * $order->getData('ls_vendor_sourced_lines') or call setData() on the $order beforehand - proving
 * the fix works even without that prior step (i.e. simulating the "reloaded order" scenario).
 *
 * OrderHelper has a very large promoted constructor (70+ dependencies, inherited from
 * AbstractHelperOmni, which now also includes `checkoutFallbackService` - see AbstractHelperOmni),
 * so - following the precedent set by BasketHelperTest - the instance under test is a partial mock
 * (constructor disabled) with only the methods this test needs to control
 * (createInstance/setOrderPayments/updateShippingAmount) mocked; prepareOrder() itself runs for
 * real, with its collaborators (itemHelper, loyaltyHelper, lsr, quoteRepository, stockHelper,
 * checkoutFallbackService) assigned directly as public promoted properties, same convention as the
 * existing itemHelper/loyaltyHelper/lsr assignments below.
 */
class OrderHelperTest extends TestCase
{
    /** @var OrderHelper&MockObject */
    private $orderHelper;

    /** @var CartRepositoryInterface&MockObject */
    private $quoteRepository;

    /** @var StockHelper&MockObject */
    private $stockHelper;

    /** @var CheckoutFallbackService&MockObject */
    private $checkoutFallbackService;

    protected function setUp(): void
    {
        $this->orderHelper = $this->getMockBuilder(OrderHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createInstance', 'setOrderPayments', 'updateShippingAmount'])
            ->getMock();

        $this->orderHelper->method('createInstance')
            ->willReturnCallback(static function (?string $entityClassName = null, array $data = []) {
                $object = (new ReflectionClass($entityClassName))->newInstanceWithoutConstructor();
                if (!empty($data['data']) && method_exists($object, 'addData')) {
                    $object->addData($data['data']);
                }
                return $object;
            });

        $this->orderHelper->method('setOrderPayments')->willReturn([]);
        $this->orderHelper->method('updateShippingAmount')
            ->willReturnCallback(static fn (array $lines) => $lines);

        $itemHelper = $this->createMock(ItemHelper::class);
        $itemHelper->method('checkAndUpdateServiceItems')->willReturn(false);
        $this->orderHelper->itemHelper = $itemHelper;

        $this->orderHelper->loyaltyHelper = $this->createMock(LoyaltyHelper::class);
        $this->orderHelper->lsr = $this->createMock(LSR::class);

        $this->quoteRepository = $this->createMock(CartRepositoryInterface::class);
        $this->orderHelper->quoteRepository = $this->quoteRepository;

        $this->stockHelper = $this->createMock(StockHelper::class);
        $this->orderHelper->stockHelper = $this->stockHelper;

        $this->checkoutFallbackService = $this->createMock(CheckoutFallbackService::class);
        $this->orderHelper->checkoutFallbackService = $this->checkoutFallbackService;
    }

    private function createOrderLine(int $lineNo, string $number, string $variantCode): MobileTransactionLine
    {
        /** @var MobileTransactionLine $line */
        $line = (new ReflectionClass(MobileTransactionLine::class))->newInstanceWithoutConstructor();
        $line->setLineno($lineNo);
        $line->setLinetype(0);
        $line->setNumber($number);
        $line->setVariantcode($variantCode);
        $line->setUomid('PCS');
        $line->setNetprice(10.0);
        $line->setPrice(12.0);
        $line->setQuantity(1.0);
        $line->setDiscountamount(0.0);
        $line->setDiscountpercent(0.0);
        $line->setNetamount(10.0);
        $line->setTaxamount(2.0);

        return $line;
    }

    /**
     * @return Order&MockObject
     */
    private function createMagentoOrder(?int $quoteId): Order
    {
        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')->willReturn('1');

        $billingAddress = $this->createMock(OrderAddress::class);
        $billingAddress->method('getFirstname')->willReturn('Jane');
        $billingAddress->method('getLastname')->willReturn('Doe');
        $billingAddress->method('getStreetLine')->willReturn('123 Main St');
        $billingAddress->method('getCity')->willReturn('Springfield');
        $billingAddress->method('getRegion')->willReturn('IL');
        $billingAddress->method('getPostcode')->willReturn('62701');
        $billingAddress->method('getCountryId')->willReturn('US');
        $billingAddress->method('getTelephone')->willReturn('555-1234');

        // getPickupStore()/getPickupDateTimeslot() are served via
        // Magento\Framework\DataObject's magic __call() (no real declared method on Order) -
        // PHPUnit 10's createMock() can only configure genuinely declared methods, so
        // addMethods() is required here, same as CheckoutSession::getNoManageStock() in
        // DataAssignObserverTest. Note: $order->getData() is intentionally NOT stubbed at all -
        // the fix under test must never call it for 'ls_vendor_sourced_lines'.
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPickupStore', 'getPickupDateTimeslot'])
            ->onlyMethods([
                'getStore',
                'getCustomerIsGuest',
                'getCustomerEmail',
                'getBillingAddress',
                'getShippingAddress',
                'getShippingMethod',
                'getIncrementId',
                'getOrderCurrencyCode',
                'getStoreId',
                'getQuoteId',
            ])
            ->getMock();
        $order->method('getStore')->willReturn($store);
        $order->method('getCustomerIsGuest')->willReturn(true);
        $order->method('getCustomerEmail')->willReturn('jane@example.com');
        $order->method('getBillingAddress')->willReturn($billingAddress);
        $order->method('getShippingAddress')->willReturn(null);
        $order->method('getShippingMethod')->willReturn(null);
        $order->method('getPickupStore')->willReturn(null);
        $order->method('getPickupDateTimeslot')->willReturn(null);
        $order->method('getIncrementId')->willReturn('100000001');
        $order->method('getOrderCurrencyCode')->willReturn('USD');
        $order->method('getStoreId')->willReturn(1);
        $order->method('getQuoteId')->willReturn($quoteId);

        return $order;
    }

    /**
     * @param MobileTransactionLine[] $lines
     */
    private function createOneListCalculateResponse(array $lines): RootMobileTransaction
    {
        /** @var MobileTransaction $mobileTransaction */
        $mobileTransaction = (new ReflectionClass(MobileTransaction::class))->newInstanceWithoutConstructor();
        $mobileTransaction->setStoreid('STORE01');
        $mobileTransaction->setMembercardno('CARD01');

        /** @var RootMobileTransaction $response */
        $response = (new ReflectionClass(RootMobileTransaction::class))->newInstanceWithoutConstructor();
        $response->setMobiletransaction($mobileTransaction);
        $response->setMobiletransactionline($lines);
        $response->setMobiletransdiscountline([]);

        return $response;
    }

    /**
     * @return Quote&MockObject
     */
    private function createFreshQuote(array $quoteItems, ?string $shippingMethod, ?string $pickupStore = null): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPickupStore'])
            ->onlyMethods(['getAllVisibleItems', 'getShippingAddress'])
            ->getMock();
        $quote->method('getAllVisibleItems')->willReturn($quoteItems);
        $quote->method('getPickupStore')->willReturn($pickupStore);

        if ($shippingMethod === null) {
            $quote->method('getShippingAddress')->willReturn(null);
        } else {
            $shippingAddress = $this->createMock(Address::class);
            $shippingAddress->method('getShippingMethod')->willReturn($shippingMethod);
            $quote->method('getShippingAddress')->willReturn($shippingAddress);
        }

        return $quote;
    }

    /**
     * Delivery/flatrate path: prepareOrder() must load the quote fresh via quoteRepository, run
     * evaluateDelivery() (not evaluate()) against its items, and build the lines map from
     * buildVendorSourcedLinesMap() - all WITHOUT ever touching $order->getData(...).
     */
    public function testPrepareOrderBuildsVendorSourcedLinesFromFreshQuoteLoadForDeliveryCart(): void
    {
        $matching = $this->createOrderLine(10000, 'ITEM1', 'VAR1');
        $other = $this->createOrderLine(10010, 'ITEM2', 'VAR2');

        $order = $this->createMagentoOrder(42);
        $quoteItem = $this->createMock(Item::class);
        $quote = $this->createFreshQuote([$quoteItem], 'flatrate_flatrate');

        $this->quoteRepository->expects($this->once())->method('get')->with(42)->willReturn($quote);

        $this->checkoutFallbackService->expects($this->once())
            ->method('evaluateDelivery')
            ->with([$quoteItem]);
        $this->checkoutFallbackService->expects($this->never())->method('evaluate');

        $this->checkoutFallbackService->method('buildVendorSourcedLinesMap')
            ->with([$quoteItem])
            ->willReturn(['ITEM1|VAR1' => ['vendor_sourcing' => true, 'sourcing_location' => 'S1']]);

        $this->stockHelper->expects($this->never())->method('getGivenItemsStockInGivenStore');

        $response = $this->createOneListCalculateResponse([$matching, $other]);
        $result = $this->orderHelper->prepareOrder($order, $response);

        $this->assertInstanceOf(RootCustomerOrderCreateV6::class, $result);
        $coLines = $result->getCustomerordercreatecolinev6();

        /** @var CustomerOrderCreateCOLineV6 $matchedLine */
        $matchedLine = $coLines[0];
        $this->assertSame('ITEM1', $matchedLine->getNumber());
        $this->assertTrue($matchedLine->getVendorsourcing());
        $this->assertSame('S1', $matchedLine->getSourcinglocation());

        /** @var CustomerOrderCreateCOLineV6 $unmatchedLine */
        $unmatchedLine = $coLines[1];
        $this->assertSame('ITEM2', $unmatchedLine->getNumber());
        $this->assertNull($unmatchedLine->getVendorsourcing());
        $this->assertNull($unmatchedLine->getSourcinglocation());
    }

    /**
     * Click & Collect path: prepareOrder() must build a per-store stock collection (same shape
     * DataAssignObserver uses) and call evaluate() with TYPE_COLLECT, not evaluateDelivery().
     */
    public function testPrepareOrderForClickAndCollectCartCallsEvaluateWithTypeCollect(): void
    {
        $line = $this->createOrderLine(10000, 'ITEM1', 'VAR1');

        $order = $this->createMagentoOrder(42);
        $quoteItem = $this->createMock(Item::class);
        $quote = $this->createFreshQuote([$quoteItem], 'clickandcollect_clickandcollect', 'STORE01');

        $this->quoteRepository->method('get')->with(42)->willReturn($quote);

        $rawStockCollection = [['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'qty' => 1.0, 'quote_item' => $quoteItem]];
        $stockResponse = $this->createMock(\Ls\Omni\Client\CentralEcommerce\Entity\RootGetInventoryMultipleOut::class);
        $updatedStockCollection = [
            ['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'qty' => 1.0, 'quote_item' => $quoteItem, 'status' => '0'],
        ];

        $this->stockHelper->expects($this->once())
            ->method('getGivenItemsStockInGivenStore')
            ->with([$quoteItem], 'STORE01')
            ->willReturn([$stockResponse, $rawStockCollection]);
        $this->stockHelper->expects($this->once())
            ->method('updateStockCollection')
            ->with($stockResponse, $rawStockCollection)
            ->willReturn($updatedStockCollection);

        $this->checkoutFallbackService->expects($this->once())
            ->method('evaluate')
            ->with($updatedStockCollection, \Ls\Omni\Helper\VendorSourcingHelper::TYPE_COLLECT, [$quoteItem]);
        $this->checkoutFallbackService->expects($this->never())->method('evaluateDelivery');

        $this->checkoutFallbackService->method('buildVendorSourcedLinesMap')->willReturn([]);

        $response = $this->createOneListCalculateResponse([$line]);
        $result = $this->orderHelper->prepareOrder($order, $response);

        $coLines = $result->getCustomerordercreatecolinev6();
        $this->assertNull($coLines[0]->getVendorsourcing());
    }

    /**
     * No-op regression: an order whose quote has no vendor-sourced items leaves
     * VendorSourcing/SourcingLocation unset on every line.
     */
    public function testPrepareOrderLeavesVendorSourcingUnsetWhenMapIsEmpty(): void
    {
        $line = $this->createOrderLine(10000, 'ITEM1', 'VAR1');

        $order = $this->createMagentoOrder(42);
        $quoteItem = $this->createMock(Item::class);
        $quote = $this->createFreshQuote([$quoteItem], 'flatrate_flatrate');
        $this->quoteRepository->method('get')->willReturn($quote);
        $this->checkoutFallbackService->method('buildVendorSourcedLinesMap')->willReturn([]);

        $response = $this->createOneListCalculateResponse([$line]);
        $result = $this->orderHelper->prepareOrder($order, $response);

        $coLines = $result->getCustomerordercreatecolinev6();
        $this->assertNull($coLines[0]->getVendorsourcing());
        $this->assertNull($coLines[0]->getSourcinglocation());
    }

    /**
     * Click & Collect vendor-sourced lines carry no suggested store (the pickup store is already
     * conveyed via StoreNo) - SourcingLocation must not be sent at all when null, not sent as an
     * empty string.
     */
    public function testPrepareOrderOmitsSourcingLocationWhenMapEntryHasNullSourcingLocation(): void
    {
        $line = $this->createOrderLine(10000, 'ITEM1', 'VAR1');

        $order = $this->createMagentoOrder(42);
        $quoteItem = $this->createMock(Item::class);
        $quote = $this->createFreshQuote([$quoteItem], 'flatrate_flatrate');
        $this->quoteRepository->method('get')->willReturn($quote);
        $this->checkoutFallbackService->method('buildVendorSourcedLinesMap')
            ->willReturn(['ITEM1|VAR1' => ['vendor_sourcing' => true, 'sourcing_location' => null]]);

        $response = $this->createOneListCalculateResponse([$line]);
        $result = $this->orderHelper->prepareOrder($order, $response);

        $coLines = $result->getCustomerordercreatecolinev6();
        $this->assertTrue($coLines[0]->getVendorsourcing());
        $this->assertNull($coLines[0]->getSourcinglocation());
    }

    /**
     * No quote id on the order (defensive edge case) must short-circuit to an empty map, with
     * neither quoteRepository nor checkoutFallbackService ever touched.
     */
    public function testPrepareOrderReturnsEmptyMapWhenOrderHasNoQuoteId(): void
    {
        $line = $this->createOrderLine(10000, 'ITEM1', 'VAR1');

        $order = $this->createMagentoOrder(null);

        $this->quoteRepository->expects($this->never())->method('get');
        $this->checkoutFallbackService->expects($this->never())->method('evaluate');
        $this->checkoutFallbackService->expects($this->never())->method('evaluateDelivery');
        $this->checkoutFallbackService->expects($this->never())->method('buildVendorSourcedLinesMap');

        $response = $this->createOneListCalculateResponse([$line]);
        $result = $this->orderHelper->prepareOrder($order, $response);

        $coLines = $result->getCustomerordercreatecolinev6();
        $this->assertNull($coLines[0]->getVendorsourcing());
    }

    /**
     * Critical regression test for the actual bug being fixed: the quote failing to load (e.g. the
     * "reloaded Order, quote already consumed/unavailable" scenario) must degrade gracefully to an
     * empty map rather than throwing out of prepareOrder() entirely.
     */
    public function testPrepareOrderReturnsEmptyMapWhenQuoteFailsToLoad(): void
    {
        $line = $this->createOrderLine(10000, 'ITEM1', 'VAR1');

        $order = $this->createMagentoOrder(42);
        $this->quoteRepository->method('get')->willThrowException(
            new NoSuchEntityException(__('Quote not found'))
        );

        $this->checkoutFallbackService->expects($this->never())->method('evaluate');
        $this->checkoutFallbackService->expects($this->never())->method('evaluateDelivery');

        $response = $this->createOneListCalculateResponse([$line]);
        $result = $this->orderHelper->prepareOrder($order, $response);

        $coLines = $result->getCustomerordercreatecolinev6();
        $this->assertNull($coLines[0]->getVendorsourcing());
    }
}
