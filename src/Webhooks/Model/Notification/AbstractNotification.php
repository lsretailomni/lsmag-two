<?php

namespace Ls\Webhooks\Model\Notification;

use Ls\Webhooks\Api\Data\ReceiverInterface;
use Ls\Webhooks\Api\Data\SenderInterface;
use Ls\Webhooks\Helper\Data;
use Ls\Webhooks\Logger\Logger;
use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\OrderInterface;

abstract class AbstractNotification extends DataObject
{
    public const RECEIVER = 'receiver';
    public const SENDER = 'sender';
    public const ORDER = 'order';

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Data
     */
    public $helper;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($data);
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Send notification
     *
     * @return mixed
     */
    abstract public function notify();

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
}
