<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Helper;

use Ls\Omni\Client\CentralEcommerce\Entity\MobileTransactionLine;
use Ls\Omni\Helper\ItemHelper;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for {@see ItemHelper::setRelatedAmountsAgainstGivenQuoteItem()} (ticket 88392).
 *
 * LS Central returns each basket line's tax-inclusive amount via Price/NetAmount+TAXAmount and
 * its tax-exclusive amount via NetPrice/NetAmount separately. Before this fix, the caller
 * (compareQuoteItemsWithOrderLinesAndSetRelatedAmounts()) derived $unitPrice from the
 * tax-inclusive amount and this method wrote it into CustomPrice - the field Magento uses to
 * render the "excl. tax" price - so US stores (real sales tax) showed a tax-inclusive price
 * where an excl-tax price was expected.
 *
 * ItemHelper has a large constructor (14 dependencies), so - following the precedent set by
 * Ls\Omni\Test\Unit\Helper\BasketHelperTest - the instance under test is a partial mock
 * (constructor disabled) with only convertToBaseCurrency() stubbed to the identity function
 * (its own currency-conversion logic is out of scope for this fix and untouched by it).
 * $quoteItem is a real Magento\Quote\Model\Quote\Item built via
 * ReflectionClass::newInstanceWithoutConstructor(), which works because AbstractItem's
 * getters/setters are plain getData()/setData() wrappers around \Magento\Framework\DataObject
 * (defaults `_data` to `[]`), mirroring how MobileTransactionLine is built in BasketHelperTest.
 */
class ItemHelperTest extends TestCase
{
    /**
     * @var ItemHelper&MockObject
     */
    private $itemHelper;

    protected function setUp(): void
    {
        $this->itemHelper = $this->getMockBuilder(ItemHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['convertToBaseCurrency'])
            ->getMock();

        $this->itemHelper->method('convertToBaseCurrency')
            ->willReturnCallback(static fn ($price) => $price);
    }

    /**
     * @param float $netprice tax-exclusive unit price
     * @param float $price tax-inclusive unit price
     * @param float $netamount tax-exclusive row total
     * @param float $taxamount tax amount for the row
     * @return MobileTransactionLine
     */
    private function createOrderLine(
        float $netprice,
        float $price,
        float $netamount,
        float $taxamount,
        float $quantity = 1.0,
        float $discountamount = 0.0
    ): MobileTransactionLine {
        /** @var MobileTransactionLine $line */
        $line = (new ReflectionClass(MobileTransactionLine::class))->newInstanceWithoutConstructor();
        $line->setNetprice($netprice)
            ->setPrice($price)
            ->setNetamount($netamount)
            ->setTaxamount($taxamount)
            ->setQuantity($quantity)
            ->setDiscountamount($discountamount);

        return $line;
    }

    /**
     * @param float $qty
     * @param float $catalogPrice regular/catalog price of the underlying product
     * @return Item
     */
    private function createQuoteItem(float $qty, float $catalogPrice): Item
    {
        $product = $this->createMock(Product::class);
        $product->method('getPrice')->willReturn($catalogPrice);

        /** @var Item $quoteItem */
        $quoteItem = (new ReflectionClass(Item::class))->newInstanceWithoutConstructor();
        // setQty()/setProduct() are overridden with real dependencies (locale formatter, event
        // manager, ...) unavailable on a constructor-disabled instance - set the raw data
        // instead, which getQty()/getProduct() (unoverridden reads, or already-present-data
        // short-circuit) both consume safely.
        $quoteItem->setData('qty', $qty)->setData('product', $product);

        return $quoteItem;
    }

    /**
     * Ticket 88392 core regression: when LS Central's NetPrice (tax-exclusive) is passed in as
     * $unitPrice, CustomPrice/OriginalCustomPrice (which drive the "excl. tax" display) must
     * end up tax-exclusive, while PriceInclTax/BasePriceInclTax must independently reflect the
     * line's own tax-inclusive Price - never the tax-exclusive $unitPrice.
     */
    public function testSetRelatedAmountsSetsCustomPriceExclTaxAndPriceInclTaxSeparately(): void
    {
        $line = $this->createOrderLine(
            netprice: 15.00,
            price: 18.00,
            netamount: 15.00,
            taxamount: 3.00
        );
        $quoteItem = $this->createQuoteItem(qty: 1.0, catalogPrice: 20.00);

        // Caller now passes the tax-exclusive NetPrice (the fix), not (NetAmount+TAXAmount)/Qty.
        $unitPrice = $line->getNetprice();
        $this->itemHelper->setRelatedAmountsAgainstGivenQuoteItem($line, $quoteItem, $unitPrice);

        $this->assertSame(
            15.00,
            $quoteItem->getCustomPrice(),
            'CustomPrice must be the tax-exclusive unit price, not the tax-inclusive one'
        );
        $this->assertSame(15.00, $quoteItem->getOriginalCustomPrice());
        $this->assertSame(
            18.00,
            $quoteItem->getPriceInclTax(),
            'PriceInclTax must independently be the line\'s tax-inclusive Price'
        );
        $this->assertSame(3.00, $quoteItem->getTaxAmount());
        $this->assertSame(15.00, $quoteItem->getRowTotal());
        $this->assertSame(18.00, $quoteItem->getRowTotalInclTax());
    }

    /**
     * The discount-driven CustomPrice branch must exhibit the same tax-exclusive/tax-inclusive
     * split as the price-mismatch branch covered above.
     */
    public function testSetRelatedAmountsKeepsCustomPriceExclTaxWhenDiscounted(): void
    {
        $line = $this->createOrderLine(
            netprice: 8.00,
            price: 9.60,
            netamount: 8.00,
            taxamount: 1.60,
            discountamount: 2.00
        );
        $quoteItem = $this->createQuoteItem(qty: 1.0, catalogPrice: 10.00);

        $unitPrice = $line->getNetprice();
        $this->itemHelper->setRelatedAmountsAgainstGivenQuoteItem($line, $quoteItem, $unitPrice);

        $this->assertSame(8.00, $quoteItem->getCustomPrice());
        $this->assertSame(9.60, $quoteItem->getPriceInclTax());
        $this->assertSame(2.00, $quoteItem->getLsDiscountAmount());
    }
}