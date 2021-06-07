<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Webhooks\Logger\Logger;
use \Ls\Webhooks\Helper\Data;
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
     * @var Data
     */
    public $helper;

    /**
     * Cancel constructor.
     * @param OrderManagementInterface $orderManagement
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        Data $helper,
        Logger $logger
    ) {
        $this->orderManagement = $orderManagement;
        $this->logger          = $logger;
        $this->helper          = $helper;
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

}
