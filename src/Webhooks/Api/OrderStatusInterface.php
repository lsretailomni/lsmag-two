<?php

namespace Ls\Webhooks\Api;

interface OrderStatusInterface
{
    /**
     * set order status Api.
     *
     * @param string $document_id
     * @param string $status
     *
     * @return string
     * @api
     *
     */
    public function set($document_id, $status);
}
