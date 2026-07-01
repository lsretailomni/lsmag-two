<?php

declare(strict_types=1);

namespace Ls\Replication\Plugin;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\CentralEcommerce\Operation\PriceListLine;
use Ls\Replication\Cron\ReplLscSalepriceviewTask;

/**
 * Routes the price replication request based on the UseSalesPrice config and the LS Central version.
 *
 * - UseSalesPrice = Yes: leave the default SalePriceView request untouched (proceed).
 * - UseSalesPrice = No and LS Central <= 26.0: use the legacy GetPriceListLine operation.
 * - UseSalesPrice = No and LS Central > 26.0: leave the default SalePriceView request untouched (proceed).
 */
class SalePriceViewRequestPlugin
{
    private LSR $lsr;

    private PriceListLine $priceListLineOperation;

    public function __construct(LSR $lsr, PriceListLine $priceListLineOperation)
    {
        $this->lsr = $lsr;
        $this->priceListLineOperation = $priceListLineOperation;
    }

    /**
     * @param ReplLscSalepriceviewTask $subject
     * @param callable $proceed
     * @param string $baseUrl
     * @param array $connectionParams
     * @param string $companyName
     * @param bool $fullRepl
     * @param int $batchSize
     * @param string $storeNo
     * @param int $lastEntryNo
     * @param string $lastKey
     * @return mixed
     */
    public function aroundMakeRequest(
        ReplLscSalepriceviewTask $subject,
        callable $proceed,
        string $baseUrl = '',
        array $connectionParams = [],
        string $companyName = '',
        bool $fullRepl = false,
        int $batchSize = 100,
        string $storeNo = '',
        int $lastEntryNo = 0,
        string $lastKey = ''
    ) {
        // makeRequest() only exposes the LS Central store number ($storeNo), not a Magento
        // store/website id, so the config is read with a null scope (default scope) here.
        $useSalesPrice = $this->lsr->getStoreConfig(LSR::SC_USE_SALES_PRICE, null);

        if (!$useSalesPrice) {
            $centralVersion = $this->lsr->getCentralVersion();

            if (version_compare($centralVersion, '26.0', '<=')) {
                // TODO PriceListLine2 (LS Central version >= 28): operation class not present
                // in the Omni client yet; routing to the legacy GetPriceListLine operation.
                return $this->priceListLineOperation;
            }
        }

        return $proceed(
            $baseUrl,
            $connectionParams,
            $companyName,
            $fullRepl,
            $batchSize,
            $storeNo,
            $lastEntryNo,
            $lastKey
        );
    }
}
