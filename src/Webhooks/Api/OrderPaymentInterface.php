<?php

namespace Ls\Webhooks\Api;

/**
 * Interface OrderPaymentInterface
 * @api
 */
interface OrderPaymentInterface
{
    /**
     * Set order payment
     *
     * @param string $OrderId
     * @param string $Status
     * @param string $Amount
     * @param string $CurrencyCode
     * @param string $Token
     * @param string $AuthCode
     * @param string $Reference
     * @param mixed $Lines
     * @return mixed
     */
    public function set($OrderId, $Status, $Amount, $CurrencyCode, $Token, $AuthCode, $Reference, $Lines);
}
