<?php

namespace Ls\Omni\Api;

/**
 * Interface PointsManagementInterface
 * @package Ls\Omni\Api
 */
interface PointsManagementInterface
{
    /**
     * @param $cartId
     * @param $pointSpent
     * @return mixed
     */
    public function updatePoints(
        $cartId,
        $pointSpent
    );
}
