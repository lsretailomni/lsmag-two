<?php
declare(strict_types=1);

namespace Ls\Omni\Model\VendorSourcing;

use Ls\Omni\Helper\VendorSourcingHelper;

/**
 * D5/FR8: a candidate pickup store is only valid if it (a) has stock of every normal item
 * (computed elsewhere, by the existing qty-based disqualification loop) AND (b) is a
 * collect-type sourcing store for every vendor-sourced item in the cart. "Vendor-sourced
 * item" here means zero-stock-everywhere + lsr_vendor_sourcing-flagged, per the same
 * ZeroStockEverywhereResolver signal used at order-submission time (solution plan §4) -
 * a single C&C order ships from one physical store, so every line (normal or sourced)
 * must be satisfiable from the SAME store.
 */
final class PickupStoreIntersectionFilter
{
    /**
     * @param string[] $qtySatisfiedStoreNavIds stores that satisfy every normal item's qty
     *                                            (existing filterStoresOnTheBasisOfQty() output)
     * @param array[] $vendorSourcedItemsOptions one VendorSourcingHelper::getOptions() result
     *                                            per zero-stock-everywhere + eligible item in the cart
     * @return string[] the valid pickup store nav-ids - intersection of (a) and (b)
     */
    public function filter(array $qtySatisfiedStoreNavIds, array $vendorSourcedItemsOptions): array
    {
        if (empty($vendorSourcedItemsOptions)) {
            return $qtySatisfiedStoreNavIds; // no-op - AC1/AC2-equivalent for the store list
        }

        $intersection = null;

        foreach ($vendorSourcedItemsOptions as $options) {
            $collectCodes = array_values(array_filter(array_column(
                array_filter($options, static fn (array $o): bool => $o['type'] === VendorSourcingHelper::TYPE_COLLECT),
                'store_code'
            )));

            $intersection = $intersection === null
                ? $collectCodes
                : array_intersect($intersection, $collectCodes);
        }

        return array_values(array_intersect($qtySatisfiedStoreNavIds, $intersection ?? []));
    }
}