<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Webhooks\Logger\Logger;
use \Ls\Webhooks\Helper\Data;
use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\ItemRepository;

/**
 * class to cancel order through webhook
 */
class Cancel
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ItemRepository
     */
    private $itemRepository;

    /**
     * Cancel constructor.
     * @param OrderManagementInterface $orderManagement
     * @param ItemRepository $itemRepository
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        ItemRepository $itemRepository,
        Data $helper,
        Logger $logger
    ) {
        $this->orderManagement = $orderManagement;
        $this->itemRepository  = $itemRepository;
        $this->helper          = $helper;
        $this->logger          = $logger;
    }

    /**
     * Cancel order
     *
     * @param $orderId
     * @return array[]
     */
    public function cancelOrder($orderId)
    {
        try {
            $this->orderManagement->cancel($orderId);
            $message = Status::SUCCESS_MESSAGE;
            return $this->helper->outputMessage(true, $message);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }

    /**
     * For cancelling order item
     *
     * @param $magOrder
     * @param $items
     * @return array[]
     * @throws NoSuchEntityException
     */
    public function cancelItems($magOrder, $items)
    {
        if ($magOrder->canCancel()) {
            $storeId = $magOrder->getStoreId();
            foreach ($items as $itemData) {
                foreach ($itemData as $itemData) {
                    $item               = $itemData['item'];
                    $cancellationAmount = $itemData['amount'];
                    $item->setQtyCanceled($item->getQtyCanceled() + $itemData['qty']);
                    $this->itemRepository->save($item);
                    $magOrder->setTotalCanceled($magOrder->getTotalCanceled() + $cancellationAmount);
                    $magOrder->setBaseTotalCanceled($magOrder->getBaseTotalCanceled() + $cancellationAmount);
                    $this->helper->getOrderRepository()->save($magOrder);
                }
            }
            $message = Status::SUCCESS_MESSAGE;

            $this->helper->processNotifications(
                $storeId,
                $magOrder,
                $items,
                $message,
                LSR::LS_NOTIFICATION_PUSH_NOTIFICATION
            );

            return $this->helper->outputMessage(true, $message);
        }

        return [];
    }
}
