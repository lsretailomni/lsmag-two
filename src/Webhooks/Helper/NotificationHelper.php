<?php
declare(strict_types=1);

namespace Ls\Webhooks\Helper;

use \Ls\Core\Model\LSR;
use \Ls\Webhooks\Model\Notification\EmailNotification;
use \Ls\Webhooks\Model\Notification\PushNotification;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Helper class to handle notification
 */
class NotificationHelper
{
    /**
     * @param LSR $lsr
     * @param PushNotification $pushNotification
     * @param EmailNotification $emailNotification
     */
    public function __construct(
        public LSR $lsr,
        public PushNotification $pushNotification,
        public EmailNotification $emailNotification
    ) {
    }

    /**
     *  Process notifications
     *
     * @param int $storeId
     * @param OrderInterface $magOrder
     * @param array $items
     * @param string $statusMsg
     * @param $orderStatus
     * @return void
     * @throws NoSuchEntityException
     */
    public function processNotifications($storeId, $magOrder, $items, $statusMsg, $orderStatus): void
    {
        $configuredNotificationType = explode(',', $this->getNotificationType($storeId));

        foreach ($configuredNotificationType as $type) {
            if ($type == LSR::LS_NOTIFICATION_EMAIL) {
                $this->emailNotification->setNotificationType($orderStatus);
                $this->emailNotification->setOrder($magOrder)->setItems($items);
                $this->emailNotification->prepareAndSendNotification();
            }

            if ($type == LSR::LS_NOTIFICATION_PUSH_NOTIFICATION) {
                $this->pushNotification->setNotificationType($statusMsg);
                $this->pushNotification->setOrder($magOrder)->setItems($items);
                $this->pushNotification->prepareAndSendNotification();
            }
        }
    }

    /**
     * Get configuration for notification type
     *
     * @param mixed $storeId
     * @return string
     */
    public function getNotificationType($storeId = null)
    {
        return $this->lsr->getNotificationType($storeId);
    }
}
