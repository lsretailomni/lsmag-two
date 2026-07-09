<?php

declare(strict_types=1);

namespace Ls\Replication\Plugin;

use Ls\Core\Model\LSR;
use Ls\Replication\Cron\ReplLscSalepriceviewTask;
use Ls\Replication\Cron\ReplLscSalesPriceTask;

/**
 * Gates the price replication cron tasks.
 *
 * The repl_price cron (ReplLscSalepriceviewTask) is now the single price-fetch task for every
 * configuration: SalePriceViewRequestPlugin routes it to the correct operation (SalePriceView,
 * PriceListLine or SalesPrice) and all responses land in the shared repl_price table, so it must
 * never be gated off. The legacy repl_sales_price cron (ReplLscSalesPriceTask) is retired and is
 * always skipped.
 */
class PriceCronGatePlugin
{
    private LSR $lsr;

    public function __construct(LSR $lsr)
    {
        $this->lsr = $lsr;
    }

    /**
     * Skip the retired sales price task; always proceed for the unified sale price view task.
     *
     * @param ReplLscSalepriceviewTask|ReplLscSalesPriceTask $subject
     * @param callable $proceed
     * @param mixed $storeId
     * @return mixed
     */
    public function aroundFetchDataGivenStore(
        ReplLscSalepriceviewTask|ReplLscSalesPriceTask $subject,
        callable $proceed,
        $storeId
    ) {
        if ($subject instanceof ReplLscSalesPriceTask) {
            // Retired: the repl_price cron now handles the UseSalesPrice = Yes case too.
            return null;
        }

        return $proceed($storeId);
    }
}
