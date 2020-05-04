<?php

namespace Ls\Webhooks\Api;

/**
 * Interface OrderPaymentInterface
 * @package Ls\Webhooks\Api
 * @api
 */
interface OrderPaymentInterface
{
    /**
     * Set order payment status API
     * @param string $documentId
     * @param string $status
     * @param string $token
     * @param string $amount
     * @return mixed
     */
    public function set($documentId, $status, $token, $amount);
}
