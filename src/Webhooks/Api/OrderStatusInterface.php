<?php

namespace Ls\Webhooks\Api;

/**
 * Interface OrderStatusInterface
 * @api
 */
interface OrderStatusInterface
{
    /**
     * Set order status API
     * @param string $OrderId
     * @param string $CardId
     * @param string $HeaderStatus
     * @param string $MsgSubject
     * @param string $MsgDetail
     * @param mixed $Lines
     * @return mixed
     */
    public function set($OrderId, $CardId, $HeaderStatus, $MsgSubject, $MsgDetail, $Lines);
}
