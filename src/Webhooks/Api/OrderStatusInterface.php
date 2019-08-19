<?php

namespace Ls\Webhooks\Api;

interface OrderStatusInterface
{
    /**
     * set order status Api.
     *
     * @api
     *
     * @param string $document_id
     * @param string $status
     *
     * @return string
     */
    public function set($document_id, $status);
}
