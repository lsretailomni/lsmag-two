<?php
declare(strict_types=1);

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
    public function set(\Ls\Webhooks\Api\Data\OrderStatusMessageInterface $orderMessage)
    {
        try {
            $data = [
                'OrderId' => $orderMessage->getOrderId(),
                'CardId' => $orderMessage->getCardId(),
                'HeaderStatus' => $orderMessage->getHeaderStatus(),
                'MsgSubject' => $orderMessage->getMsgSubject(),
                'MsgDetail' => $orderMessage->getMsgDetail(),
                'Lines' => $this->formatOrderLines($orderMessage->getLines()),
                'orderKOTStatus' => $orderMessage->getOrderKOTStatus()
            ];
            $this->logger->info('OrderStatus = ', $data);

            return $this->status->process($data);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->status->getHelperObject()->outputMessage(false, $e->getMessage());
        }
    }

    /**
     * Get formatted lines
     *
     * @param \Ls\Webhooks\Api\Data\OrderLineInterface[] $lines
     * @return array
     */
    public function formatOrderLines(array $lines)
    {
        $formattedLines = [];
        if (!empty($lines)) {
            foreach ($lines as $line) {
                $formattedLines[] = [
                    'NewStatus' => $line->getNewStatus(),
                    'ItemId' => $line->getItemId(),
                    'Quantity' => $line->getQuantity(),
                    'UnitOfMeasureId' => $line->getUnitOfMeasureId(),
                    'VariantId' => $line->getVariantId(),
                    'Amount' => $line->getAmount(),
                ];
            }
        }

        return $formattedLines;
    }
}
