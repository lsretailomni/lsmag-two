<?php

declare(strict_types=1);

namespace Ls\Replication\Plugin;

use Ls\Omni\Client\CentralEcommerce\Entity\SalesPrice;
use Ls\Replication\Cron\ReplLscSalepriceviewTask;
use Ls\Replication\Model\SalesPriceToPriceViewMapper;

/**
 * Reshapes SalesPrice (GetSalesPrice) response records into repl_salepriceview properties
 * before ReplLscSalepriceviewTask persists them into the shared repl_price table.
 *
 * When SalePriceViewRequestPlugin routes the request to the SalesPrice operation, the
 * response records are SalesPrice entities whose data keys do not match the property space
 * the save loop expects. This plugin translates them via {@see SalesPriceToPriceViewMapper};
 * for any other source type it is a no-op.
 */
class SalesPriceSourceReshapePlugin
{
    private SalesPriceToPriceViewMapper $mapper;

    public function __construct(SalesPriceToPriceViewMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * @param ReplLscSalepriceviewTask $subject
     * @param array $properties
     * @param mixed $source
     * @return array
     */
    public function beforeSaveSource(
        ReplLscSalepriceviewTask $subject,
        $properties,
        $source
    ): array {
        if ($source instanceof SalesPrice) {
            $this->mapper->reshape($source);
        }

        return [$properties, $source];
    }
}
