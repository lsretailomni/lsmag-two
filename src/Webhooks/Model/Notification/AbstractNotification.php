<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Notification;

use \Ls\Webhooks\Api\Data\ReceiverInterface;
use \Ls\Webhooks\Api\Data\SenderInterface;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

abstract class AbstractNotification extends DataObject
{
    public const RECEIVER = 'receiver';
    public const SENDER = 'sender';
    public const ORDER = 'order';
    public const ITEMS = 'items';
    public const NOTIFICATION_TYPE = 'notification_type';

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Data
     */
    public $helper;

    /**
     * @var ReceiverFactory
     */
    public $receiver;

    /**
     * @var SenderFactory
     */
    public $sender;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param ReceiverFactory $receiverFactory
     * @param SenderFactory $senderFactory
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        ReceiverFactory $receiverFactory,
        SenderFactory $senderFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->helper   = $helper;
        $this->logger   = $logger;
        $this->receiver = $receiverFactory;
        $this->sender   = $senderFactory;
    }

    /**
     * Send notification
     *
     * @return mixed
     */
    abstract public function notify();

    /**
     * Is notification enabled
     *
     * @return bool
     */
    abstract public function isEnabled();

    /**
     * Prepare and send notification
     *
     * @throws NoSuchEntityException
     */
    public function prepareAndSendNotification()
    {
        if ($this->isEnabled()) {
            $order = $this->getOrder();

            /**
             * @var SenderInterface $sender
             */
            $sender = $this->sender->create();
            $sender
                ->setSenderEmail($this->helper->getStoreEmail($this->getStoreId()))
                ->setSenderName($order->getStore()->getFrontEndName());
            /**
             * @var ReceiverInterface $receiver
             */
            $receiver = $this->receiver->create();
            $receiver
                ->setReceiverEmail($order->getCustomerEmail())
                ->setReceiverName($order->getCustomerName());
            $this->setSender($sender)->setReceiver($receiver)->notify();
        }
    }

    /**
     * Get receiver
     *
     * @return ReceiverInterface
     */
    public function getReceiver()
    {
        return $this->getData(self::RECEIVER);
    }

    /**
     * Set receiver
     *
     * @param ReceiverInterface $receiver
     * @return $this
     */
    public function setReceiver(ReceiverInterface $receiver)
    {
        $this->setData(self::RECEIVER, $receiver);

        return $this;
    }

    /**
     * Get sender
     *
     * @return SenderInterface
     */
    public function getSender()
    {
        return $this->getData(self::SENDER);
    }

    /**
     * Set sender
     *
     * @param SenderInterface $sender
     * @return $this
     */
    public function setSender(SenderInterface $sender)
    {
        $this->setData(self::SENDER, $sender);

        return $this;
    }

    /**
     * Get order
     *
     * @return OrderInterface
     */
    public function getOrder()
    {
        return $this->getData(self::ORDER);
    }

    /**
     * Set order
     *
     * @param OrderInterface $order
     * @return $this
     */
    public function setOrder(OrderInterface $order)
    {
        $this->setData(self::ORDER, $order);

        return $this;
    }

    /**
     * Get Store id
     *
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->getOrder()->getStoreId();
    }

    /**
     * Get items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->getData(self::ITEMS);
    }

    /**
     * Set items
     *
     * @param array $items
     * @return $this
     */
    public function setItems(array $items)
    {
        $this->setData(self::ITEMS, $items);

        return $this;
    }

    /**
     * Get notification type
     *
     * @return string
     */
    public function getNotificationType()
    {
        return $this->getData(self::NOTIFICATION_TYPE);
    }

    /**
     * Set notification type
     *
     * @param string $notificationType
     * @return $this
     */
    public function setNotificationType(string $notificationType)
    {
        $this->setData(self::NOTIFICATION_TYPE, $notificationType);

        return $this;
    }
}
