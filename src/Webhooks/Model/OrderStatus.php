<?php

namespace Ls\Webhooks\Model;

use Exception;
use \Ls\Webhooks\Api\OrderStatusInterface;
use \Ls\Webhooks\Logger\Logger;
use \Ls\Webhooks\Model\Order\Status;

/**
 * Class for handling OrderStatus
 */
class OrderStatus implements OrderStatusInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Status
     */
    private $status;

    /**
     * OrderStatus constructor.
     * @param Status $status
     * @param Logger $logger
     */
    public function __construct(
        Status $status,
        Logger $logger
    ) {
        $this->status = $status;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function set($orderId, $cardId, $headerStatus, $msgSubject, $msgDetail, $lines)
    {
        try {
            $data = [
                'OrderId'        => $orderId,
                'CardId'         => $cardId,
                'HeaderStatus'   => $headerStatus,
                'MsgSubject'     => $msgSubject,
                'MsgDetail'      => $msgDetail,
                'Lines'          => $lines,
            ];
            $this->logger->info('OrderStatus = ', $data);
            $this->status->process($data);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
