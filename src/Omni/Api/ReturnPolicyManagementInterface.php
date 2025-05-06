<?php

namespace Ls\Omni\Api;

/**
 * Return policy management Interface class
 */
interface ReturnPolicyManagementInterface
{
    /**
     * Get return policy data
     *
     * @param string $itemId
     * @param string $variantId
     * @param string $storeId
     * @param boolean $variantIdIsSku
     * @return mixed
     */
    public function getReturnPolicy(
        $itemId,
        $variantId,
        $storeId,
        $variantIdIsSku = false
    );
}
