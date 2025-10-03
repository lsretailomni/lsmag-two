<?php
declare(strict_types=1);

namespace Ls\Webhooks\Api;

use Ls\Webhooks\Api\Data\OrderShipmentMessageInterface;

interface OrderShipmentInterface
{
    /**
     * Accepts the incoming order status update webhook
     *
     * @param \Ls\Webhooks\Api\Data\OrderShipmentMessageInterface $orderShipping
     * @return bool
     */
    public function set(OrderShipmentMessageInterface $orderShipping);
}
