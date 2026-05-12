<?php
declare(strict_types=1);

namespace Ls\Omni\Api;

interface PointsManagementInterface
{
    /**
     * Update points
     *
     * @param $cartId
     * @param $pointSpent
     * @return mixed
     */
    public function updatePoints(
        $cartId,
        $pointSpent
    );
}
