<?php

declare(strict_types=1);

namespace Ls\Replication\Model;

use Ls\Omni\Client\CentralEcommerce\Entity\SalePriceView;
use Ls\Omni\Client\CentralEcommerce\Entity\SalesPrice;

/**
 * Reshapes a SalesPrice (GetSalesPrice) response entity into the repl_salepriceview
 * property space so it persists into the shared repl_price table.
 *
 * The AbstractReplicationTask save loop copies data from the source entity using the
 * ARRAY KEYS of ReplSalepriceview::getDbColumnsMapping() (the LS Central field-name
 * strings such as "Asset No."). SalesPrice carries the same values under different keys,
 * so this mapper copies each value onto the source under the SalePriceView constant keys.
 */
class SalesPriceToPriceViewMapper
{
    /**
     * The repl_price status value for an active price. SyncPrice only processes Status = 1.
     */
    private const STATUS_ACTIVE = 1;

    /**
     * Copy SalesPrice values onto the source entity under the SalePriceView field-name keys.
     *
     * @param SalesPrice $source
     * @return void
     */
    public function reshape(SalesPrice $source): void
    {
        // AssetNo resolves the product (-> repl_price.ItemId), so it must be the item number.
        $source->setData(SalePriceView::ASSET_NO, $source->getItemNo());

        // SourceNo -> repl_price.SaleCode, which SyncPrice's AC9 price-group filter matches
        // against the store's configured price groups. It must therefore be the sales code
        // (customer price group), not the item number: a blank SalesCode is treated as a
        // universal All-Customers price, mirroring native SalePriceView semantics.
        $source->setData(SalePriceView::SOURCE_NO, $source->getSalesCode());

        $source->setData(SalePriceView::VARIANT_CODE, $source->getVariantCode());
        $source->setData(SalePriceView::UNIT_OF_MEASURE_CODE, $source->getUnitOfMeasureCode());
        $source->setData(SalePriceView::CURRENCY_CODE, $source->getCurrencyCode());
        $source->setData(SalePriceView::STARTING_DATE, $source->getStartingDate());
        $source->setData(SalePriceView::ENDING_DATE, $source->getEndingDate());
        $source->setData(SalePriceView::MINIMUM_QUANTITY, $source->getMinimumQuantity());
        $source->setData(SalePriceView::UNIT_PRICE, $source->getUnitPrice());
        $source->setData(SalePriceView::LSC_UNIT_PRICE_INCLUDING_VAT, $source->getLscUnitPriceIncludingVat());
        $source->setData(SalePriceView::PRICE_INCLUDES_VAT, $source->getPriceIncludesVat());
        $source->setData(SalePriceView::VAT_BUS_POSTING_GR_PRICE, $source->getVatBusPostingGrPrice());
        $source->setData(SalePriceView::ALLOW_INVOICE_DISC, $source->getAllowInvoiceDisc());
        $source->setData(SalePriceView::ALLOW_LINE_DISC, $source->getAllowLineDisc());

        // Approved: PriceListCode = SalesCode.
        $source->setData(SalePriceView::PRICE_LIST_CODE, $source->getSalesCode());

        // Approved: persist the raw numeric SalesType as SourceType (no enum translation).
        $source->setData(SalePriceView::SOURCE_TYPE, $source->getSalesType());

        // GetSalesPrice response entities carry NO Status field, but SyncPrice's guards
        // (isFuturePrice/isValidPrice) skip any row whose getStatus() !== '1'. Every reshaped
        // SalesPrice record is therefore forced Active so the price is evaluated and applied;
        // date-based deferral of future-dated rows is still handled by StartingDate/EndingDate.
        $source->setData(SalePriceView::STATUS, self::STATUS_ACTIVE);

        // SalesPrice has no LineNo. The repl_price unique key is
        // StoreId + PriceListCode + scope_id + LineNumber, so synthesize a deterministic,
        // non-negative LineNo from the natural key to keep each row's unique key distinct.
        $source->setData(SalePriceView::LINE_NO, $this->synthesizeLineNo($source));
    }

    /**
     * Build a deterministic LineNo for a SalesPrice row that fits repl_price.LineNumber.
     *
     * repl_price.LineNumber is a signed INT (max 2,147,483,647), but crc32() returns a value in
     * [0, 2^32-1]; unmasked values above the signed-INT max are rejected by MySQL strict mode
     * (the exception is swallowed in saveSource, silently dropping rows). Masking with
     * 0x7FFFFFFF keeps the result in [0, 2^31-1] — always non-negative, always within the column
     * range — while remaining deterministic (same natural key -> same LineNo, correct upsert).
     *
     * The natural key includes every field that distinguishes two repl_price rows which are NOT
     * separated by the unique key (StoreId+PriceListCode+scope_id+LineNumber). In particular,
     * PriceListCode now holds the SalesCode, and MinimumQuantity is not part of the unique key,
     * so quantity-break lines (same item/variant/uom/currency/dates, different MinimumQuantity)
     * must hash distinctly to avoid colliding onto one unique key.
     *
     * @param SalesPrice $source
     * @return int
     */
    private function synthesizeLineNo(SalesPrice $source): int
    {
        $naturalKey = implode(
            '|',
            [
                (string) $source->getItemNo(),
                (string) $source->getVariantCode(),
                (string) $source->getUnitOfMeasureCode(),
                (string) $source->getCurrencyCode(),
                (string) $source->getStartingDate(),
                (string) $source->getEndingDate(),
                (string) $source->getSalesCode(),
                (string) $source->getSalesType(),
                (string) $source->getMinimumQuantity(),
            ]
        );

        // Mask to 31 bits so the value is non-negative and fits the signed-INT LineNumber column.
        // phpcs:ignore Magento2.Security.InsecureFunction
        return crc32($naturalKey) & 0x7FFFFFFF;
    }
}
