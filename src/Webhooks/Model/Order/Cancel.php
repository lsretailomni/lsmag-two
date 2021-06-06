<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Webhooks\Logger\Logger;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Item;

/**
 * class to cancel order through webhook
 */
class Cancel
{
    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var OrderManagementInterface
     */
    public $orderManagement;

    /**
     * Cancel constructor.
     * @param OrderManagementInterface $orderManagement
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        Logger $logger
    ) {
        $this->orderManagement = $orderManagement;
        $this->logger          = $logger;
    }

    /**
     * cancel order
     * @param $orderId
     */
    public function cancelOrder($orderId)
    {
        try {
            $this->orderManagement->cancel($orderId);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function cancelPartialItem($magOrder, $skus)
    {
        /** @var Item $item */
        foreach ($magOrder->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            if (array_key_exists($item->getSku(), $skus)) {
                $item->cancel();
                $item->save();
            }
        }
    }

}
