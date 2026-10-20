<?php
declare(strict_types=1);

namespace Ls\Omni\Model\VendorSourcing;

use Ls\Omni\Client\CentralEcommerce\Entity\RootGetInventoryMultipleOut;

/**
 * Classifies whether a stock-collection row has sufficient inventory at ANY store, given
 * an all-store aggregate GetInventoryMultipleV2 response (StockHelper::getGivenItemsStockInGivenStore()
 * called with no store id - see solution plan §2.7) and the StockHelper::mergeStockCollection()-shaped
 * rows being checked. Deliberately does not reuse StockHelper::updateStockCollection(), which assumes
 * one buffer row per (item,variant) and silently last-row-wins across stores (solution plan §2.7 risk
 * note) - this class aggregates across every store's row instead.
 */
class ZeroStockEverywhereResolver
{
    /**
     * @param RootGetInventoryMultipleOut|null $allStoreResponse
     * @param array $stockCollectionRows rows shaped like StockHelper::mergeStockCollection()'s output
     *                                   (item_id, variant_id, qty, ...)
     * @return array<string,bool> keyed "item_id|variant_id" => true if at least one store's
     *                             buffer row covers the requested qty (NOT zero-stock-everywhere)
     */
    public function resolve(?RootGetInventoryMultipleOut $allStoreResponse, array $stockCollectionRows): array
    {
        $result = [];

        foreach ($stockCollectionRows as $row) {
            $key = $row['item_id'] . '|' . $row['variant_id'];
            $result[$key] = false;

            if ($allStoreResponse === null) {
                continue;
            }

            foreach ($allStoreResponse->getInventorybufferout() as $bufferRow) {
                if ($bufferRow->getNumber() == $row['item_id'] &&
                    $bufferRow->getVariant() == $row['variant_id'] &&
                    ceil($bufferRow->getInventory()) >= $row['qty']
                ) {
                    $result[$key] = true;
                    break;
                }
            }
        }

        return $result;
    }
}
