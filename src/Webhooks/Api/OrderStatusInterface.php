<?php

namespace Ls\Webhooks\Api;

interface OrderStatusInterface
{
    /**
     * Set order status API
     * @param string $document_id
     * @param string $status
     * @return string
     * @api
     */
    public function set($document_id, $status);
}
