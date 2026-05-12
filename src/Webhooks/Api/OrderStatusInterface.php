<?php
declare(strict_types=1);

namespace Ls\Webhooks\Api;

use Ls\Webhooks\Api\Data\OrderStatusMessageInterface;

interface OrderStatusInterface
{
    /**
     * Accepts the incoming order status update webhook
     *
     * @param \Ls\Webhooks\Api\Data\OrderStatusMessageInterface $orderMessage
     * @return bool
     */
    public function set(OrderStatusMessageInterface $orderMessage);
}
