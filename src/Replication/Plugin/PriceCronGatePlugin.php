<?php

declare(strict_types=1);

namespace Ls\Replication\Plugin;

use Ls\Core\Model\LSR;
use Ls\Replication\Cron\ReplLscSalepriceviewTask;
use Ls\Replication\Cron\ReplLscSalesPriceTask;

/**
 * Gates the price replication cron tasks based on the UseSalesPrice config.
 *
 * Only one of the two price-fetch cron tasks should run for a given configuration:
 * - UseSalesPrice = Yes: run ReplLscSalesPriceTask, skip ReplLscSalepriceviewTask.
 * - UseSalesPrice = No:  run ReplLscSalepriceviewTask, skip ReplLscSalesPriceTask.
 */
class PriceCronGatePlugin
{
    private LSR $lsr;

    public function __construct(LSR $lsr)
    {
        $this->lsr = $lsr;
    }

    /**
     * Skip the fetch when the target task is gated off for the current configuration.
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
        $useSalesPrice = (bool) $this->lsr->getStoreConfig(LSR::SC_USE_SALES_PRICE, $storeId);

        if ($subject instanceof ReplLscSalesPriceTask) {
            // The sales price task only runs when UseSalesPrice = Yes.
            if (!$useSalesPrice) {
                return null;
            }
        } elseif ($subject instanceof ReplLscSalepriceviewTask) {
            // The legacy sale price view task only runs when UseSalesPrice = No.
            if ($useSalesPrice) {
                return null;
            }
        }

        return $proceed($storeId);
    }
}
