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
     *
     * @param string $OrderId
     * @param string $HeaderStatus
     * @param string $MsgSubject
     * @param string $MsgDetail
     * @param string $CardId
     * @param mixed $Lines
     * @param string $OrderKOTStatus
     * @return mixed
     */
    public function set($OrderId, $HeaderStatus, $MsgSubject, $MsgDetail, $CardId = null, $Lines = null, $OrderKOTStatus = null);
}
