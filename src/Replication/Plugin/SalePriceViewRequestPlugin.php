<?php

declare(strict_types=1);

namespace Ls\Replication\Plugin;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\CentralEcommerce\Operation\PriceListLine;
use Ls\Omni\Client\CentralEcommerce\Operation\SalesPrice;
use Ls\Replication\Cron\ReplLscSalepriceviewTask;

/**
 * Routes the price replication request to the correct LS Central web-service operation.
 *
 * The subject task ({@see ReplLscSalepriceviewTask}) defaults to the SalePriceView
 * operation (ODataRequest_GetPriceListLine2). Depending on the UseSalesPrice config and
 * the connected LS Central version, the request is routed to one of three operations:
 *
 * - UseSalesPrice = Yes OR LS Central version < 26: SalesPrice (GetSalesPrice).
 * - LS Central version in [26, 28):                 PriceListLine (legacy GetPriceListLine).
 * - LS Central version >= 28:                       SalePriceView (GetPriceListLine2, default proceed).
 *
 * All three operations feed the same repl_price table; SalesPrice records are reshaped into
 * repl_salepriceview properties by {@see \Ls\Replication\Model\SalesPriceToPriceViewMapper}.
 */
class SalePriceViewRequestPlugin
{
    private LSR $lsr;

    private PriceListLine $priceListLineOperation;

    private SalesPrice $salesPriceOperation;

    public function __construct(
        LSR $lsr,
        PriceListLine $priceListLineOperation,
        SalesPrice $salesPriceOperation
    ) {
        $this->lsr = $lsr;
        $this->priceListLineOperation = $priceListLineOperation;
        $this->salesPriceOperation = $salesPriceOperation;
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
        // fetchDataGivenStore() calls $lsr->setStoreId($storeId) before makeRequest(), so the
        // current store is the store being replicated. Read the config with that store id to
        // avoid the previous default-scope (null) read bug.
        $storeId        = $this->lsr->getCurrentStoreId();
        $useSalesPrice  = (bool) $this->lsr->getStoreConfig(LSR::SC_USE_SALES_PRICE, $storeId);
        $centralVersion = $this->lsr->getCentralVersion($storeId);

        if ($useSalesPrice || version_compare((string) $centralVersion, '26', '<')) {
            // GetSalesPrice for UseSalesPrice = Yes or LS Central version < 26.
            return $this->configureOperation(
                $this->salesPriceOperation,
                $fullRepl,
                $batchSize,
                $storeNo,
                $lastEntryNo,
                $lastKey
            );
        }

        if (version_compare((string) $centralVersion, '28', '<')) {
            // Legacy GetPriceListLine for LS Central version in [26, 28).
            return $this->configureOperation(
                $this->priceListLineOperation,
                $fullRepl,
                $batchSize,
                $storeNo,
                $lastEntryNo,
                $lastKey
            );
        }

        // SalePriceView (GetPriceListLine2) for LS Central version >= 28.
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

    /**
     * Set the operation input on the routed operation, mirroring ReplLscSalepriceviewTask::makeRequest.
     *
     * The previous implementation returned the injected operation without its pagination input,
     * dropping storeNo/batchSize/fullRepl/lastEntryNo/lastKey. This restores them.
     *
     * @param PriceListLine|SalesPrice $operation
     * @param bool $fullRepl
     * @param int $batchSize
     * @param string $storeNo
     * @param int $lastEntryNo
     * @param string $lastKey
     * @return PriceListLine|SalesPrice
     */
    private function configureOperation(
        PriceListLine|SalesPrice $operation,
        bool $fullRepl,
        int $batchSize,
        string $storeNo,
        int $lastEntryNo,
        string $lastKey
    ): PriceListLine|SalesPrice {
        $operation->setOperationInput([
            'storeNo'     => $storeNo,
            'batchSize'   => $batchSize,
            'fullRepl'    => $fullRepl,
            'lastEntryNo' => $lastEntryNo,
            'lastKey'     => $lastKey,
        ]);

        return $operation;
    }
}
