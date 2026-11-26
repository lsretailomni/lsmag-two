<?php
declare(strict_types=1);

namespace Ls\CustomerGraphQl\Test\Unit\Helper;

use Ls\Core\Model\LSR;
use Ls\CustomerGraphQl\Helper\DataHelper;
use Ls\Omni\Client\Ecommerce\Entity\Address;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntryLine;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntryPayment;
use Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use Ls\Omni\Helper\Data;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\LoyaltyHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\OmniGraphQl\Helper\DataHelper as Helper;
use Magento\Directory\Model\Currency;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;

/**
 * Unit test coverage for DataHelper::getSaleEntry() (ADO 88392 / Jira LSTS-47171 follow-up).
 *
 * Bug: the 'total_net_amount' field of the getSalesEntry/getSalesEntries GraphQL query
 * (customer order history API) was populated from $salesEntry->getTotalAmount() (tax-inclusive)
 * instead of $salesEntry->getTotalNetAmount() (tax-exclusive) - the same tax-direction mistake
 * already fixed elsewhere under ADO 88392, but in the CustomerGraphQl order-history mapper rather
 * than the checkout cart.
 */
class DataHelperTest extends TestCase
{
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var Currency
     */
    private $currencyHelper;

    public function setUp(): void
    {
        $loyaltyHelper = $this->createMock(LoyaltyHelper::class);
        $orderHelper   = $this->createMock(OrderHelper::class);
        $helper        = $this->createMock(Helper::class);
        $data          = $this->createMock(Data::class);
        $data->method('getTenderTypesPaymentMapping')->willReturn([]);
        $this->currencyHelper = $this->createMock(Currency::class);
        // Deterministic, human-readable stand-in for real currency formatting so assertions can
        // compare against the raw numeric values passed to formatValue().
        $this->currencyHelper->method('format')
            ->willReturnCallback(static function ($price) {
                return sprintf('%.2f', $price);
            });
        $lsr        = $this->createMock(LSR::class);
        $itemHelper = $this->createMock(ItemHelper::class);

        $this->dataHelper = new DataHelper(
            $loyaltyHelper,
            $orderHelper,
            $helper,
            $data,
            $this->currencyHelper,
            $lsr,
            $itemHelper
        );
    }

    /**
     * @param float $totalAmount Tax-inclusive order total.
     * @param float $totalNetAmount Tax-exclusive order total.
     * @return SalesEntry
     */
    private function makeSalesEntry(float $totalAmount, float $totalNetAmount): SalesEntry
    {
        $salesEntry = $this->createMock(SalesEntry::class);
        $salesEntry->method('getTotalAmount')->willReturn($totalAmount);
        $salesEntry->method('getTotalNetAmount')->willReturn($totalNetAmount);
        $salesEntry->method('getTotalDiscount')->willReturn(0.0);
        $salesEntry->method('getContactAddress')->willReturn(new Address());
        $salesEntry->method('getShipToAddress')->willReturn(new Address());
        $salesEntry->method('getPayments')->willReturn(new ArrayOfSalesEntryPayment());
        $salesEntry->method('getLines')->willReturn(new ArrayOfSalesEntryLine());

        return $salesEntry;
    }

    /**
     * FR/ADO 88392 follow-up: 'total_net_amount' must be sourced from getTotalNetAmount()
     * (tax-exclusive, 80.0), never getTotalAmount() (tax-inclusive, 100.0). The two values are
     * deliberately different so this assertion fails against the pre-fix code, which passed
     * getTotalAmount() for both 'total_amount' and 'total_net_amount'.
     *
     * 'total_amount' and 'total_tax_amount' are asserted alongside as a regression guard - they
     * were already correct and must remain unaffected by this fix.
     */
    public function testGetSaleEntryUsesTotalNetAmountNotTotalAmountForTotalNetAmountField(): void
    {
        $salesEntry = $this->makeSalesEntry(100.0, 80.0);

        $magOrder = $this->createMock(Order::class);
        $magOrder->method('getIncrementId')->willReturn('100000001');
        $magOrder->method('getOrderCurrencyCode')->willReturn('USD');

        $result = $this->dataHelper->getSaleEntry($salesEntry, $magOrder);

        $this->assertSame(
            '80.00',
            $result['total_net_amount'],
            'total_net_amount must be formatted from getTotalNetAmount() (tax-exclusive), not getTotalAmount()'
        );
        $this->assertSame('100.00', $result['total_amount']);
        $this->assertSame('20.00', $result['total_tax_amount']);
    }
}
