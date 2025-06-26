<?php
declare(strict_types=1);

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
     * @param ?string $variantId
     * @param string $storeId
     * @param bool $variantIdIsSku
     * @return mixed
     */
    public function getReturnPolicy(
        string $itemId,
        ?string $variantId,
        string $storeId,
        bool $variantIdIsSku = false
    );
}
