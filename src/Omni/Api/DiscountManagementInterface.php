<?php

namespace Ls\Omni\Api;

/**
 * Interface DiscountManagementInterface
 * @package Ls\Omni\Api
 */
interface DiscountManagementInterface
{
    /**
     * @param string $cartId
     * @return mixed
     */
    public function checkDiscountValidity(
        $cartId
    );
}
