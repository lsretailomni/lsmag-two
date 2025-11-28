<?php

namespace Ls\Webhooks\Api;

interface OrderReturnsInterface
{
    /**
     * Create order returns
     *
     * @param string $OrderId
     * @param string $ReturnType
     * @param string $Amount
     * @param mixed $Lines
     * @return mixed
     */
    public function set(string $OrderId, string $ReturnType, string $Amount, $Lines);
}