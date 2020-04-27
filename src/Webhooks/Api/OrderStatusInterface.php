<?php

namespace Ls\Webhooks\Api;

/**
 * Interface OrderStatusInterface
 * @package Ls\Webhooks\Api
 * @api
 */
interface OrderStatusInterface
{
    /**
     * Set order status API
     * @param string $documentId
     * @param string $status
     * @return mixed
     */
    public function set($documentId, $status);
}
