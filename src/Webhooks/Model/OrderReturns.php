<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model;

use \Ls\Webhooks\Api\Data\OrderReturnsMessageInterface;
use \Ls\Webhooks\Api\OrderReturnsInterface;
use \Ls\Webhooks\Model\Order\Returns;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;

/**
 * Class for handling order returns
 */
class OrderReturns implements OrderReturnsInterface
{
    /**
     * @param Logger $logger
     * @param Returns $returns
     * @param Data $helper
     */
    public function __construct(
        public Logger $logger,
        public Returns $returns,
        public Data $helper
    ) {
    }

    /**
     * @inheritdoc
     */
    public function set(OrderReturnsMessageInterface $orderReturns)
    {
        try {
            $data = [
                'OrderId'    => $orderReturns->getOrderId(),
                'ReturnType' => $orderReturns->getReturnType(),
                'Amount'     => $orderReturns->getAmount(),
                'Lines'      => $orderReturns->getLines()
            ];

            $this->logger->info('OrderReturns = ', $data);

            if (!empty($data['OrderId'])) {
                return $this->returns->returns($data);
            }

            return $this->helper->outputMessage(false, 'Order Id is not valid.');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }
}
