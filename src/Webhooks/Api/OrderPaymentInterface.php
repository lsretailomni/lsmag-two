<?php
declare(strict_types=1);

namespace Ls\Webhooks\Api;

use Ls\Webhooks\Api\Data\OrderPaymentMessageInterface;

interface OrderPaymentInterface
{
    /**
     * Accepts the incoming order status update webhook
     *
     * @param \Ls\Webhooks\Api\Data\OrderPaymentMessageInterface $orderPayment
     * @return \Ls\Webhooks\Api\Data\OrderPaymentResponseInterface
     */
    public function set(OrderPaymentMessageInterface $orderPayment);
}
