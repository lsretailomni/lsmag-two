<?php

namespace Ls\Webhooks\Api;

/**
 * Interface OrderStatusInterface
 * @api
 */
interface OrderStatusInterface
{
    /**
     * Set order status API
     * @param mixed $orderMessage
     * @return mixed
     */
    public function set($orderMessage);
}
