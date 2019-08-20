<?php

namespace Ls\Webhooks\Api;

interface OrderPaymentInterface
{
    /**
     * set order status Api.
     *
     * @api
     *
     * @param string $document_id
     * @param string $status
     * @param string $token
     * @param double $amount
     *
     * @return string
     */
    public function set($document_id, $status, $token, $amount);
}
