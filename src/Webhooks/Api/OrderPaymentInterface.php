<?php

namespace Ls\Webhooks\Api;

interface OrderPaymentInterface
{
    /**
     * set order status Api.
     *
     * @param string $document_id
     * @param string $status
     * @param string $token
     * @param double $amount
     *
     * @return string
     * @api
     *
     */
    public function set($document_id, $status, $token, $amount);
}
