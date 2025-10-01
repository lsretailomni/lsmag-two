<?php
namespace Ls\Webhooks\Api;

use Ls\Webhooks\Api\Data\OrderMessageInterface;

interface OrderStatusInterface
{
    /**
     * Accepts the incoming order status update webhook
     *
     * @param \Ls\Webhooks\Api\Data\OrderMessageInterface $orderMessage
     * @return bool
     */
    public function set(OrderMessageInterface $orderMessage);
}
