<?php

namespace Ls\Webhooks\Api;

interface OrderPaymentInterface
{
    /**
     * Set order payment status API
     * @param $document_id
     * @param $status
     * @param $token
     * @param $amount
     * @return string
     * @api
     */
    public function set($document_id, $status, $token, $amount);
}
