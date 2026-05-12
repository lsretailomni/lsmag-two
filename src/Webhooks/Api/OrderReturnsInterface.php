<?php

namespace Ls\Webhooks\Api;

use \Ls\Webhooks\Api\Data\OrderReturnsMessageInterface;

interface OrderReturnsInterface
{
    /**
     * Create order returns
     *
     * @param OrderReturnsMessageInterface $message
     * @return mixed
     */
    public function set(OrderReturnsMessageInterface $orderReturns);
}
