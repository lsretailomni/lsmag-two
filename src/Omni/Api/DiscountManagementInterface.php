<?php
declare(strict_types=1);

namespace Ls\Omni\Api;

interface DiscountManagementInterface
{
    /**
     * Check discount validity on given cart
     *
     * @param string $cartId
     * @return mixed
     */
    public function checkDiscountValidity(
        $cartId
    );
}
