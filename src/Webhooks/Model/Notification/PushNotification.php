<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Notification;

use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\Event\ManagerInterface;

/**
 * Notification email
 */
class PushNotification extends AbstractNotification
{
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param ReceiverFactory $receiverFactory
     * @param SenderFactory $senderFactory
     * @param ManagerInterface $eventManager
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        ReceiverFactory $receiverFactory,
        SenderFactory $senderFactory,
        ManagerInterface $eventManager,
        array $data = []
    ) {
        parent::__construct($helper, $logger, $receiverFactory, $senderFactory, $data);

        $this->eventManager = $eventManager;
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function notify()
    {
        $order              = $this->getOrder();
        $notificationStatus = $this->getNotificationType();
        $storeId            = $this->getStoreId();
        $sender             = $this->getSender();
        $receiver           = $this->getReceiver();
        $ccStoreName        = '';
        if($order->getPickupStore()) {
            $ccStoreName = $this->helper->getStoreName($order->getPickupStore());
        }

        try {
            $this->eventManager->dispatch(
                'ls_push_notification_send',
                [
                    'order'               => $order,
                    'notification_status' => $notificationStatus,
                    'store_id'            => $storeId,
                    'sender'              => $sender,
                    'receiver'            => $receiver,
                    'cc_store_name'       => $ccStoreName,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Is notification enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        $order        = $this->getOrder();

        return !empty($order->getLsSubscriptionId());
    }
}
