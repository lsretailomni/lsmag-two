<?php

namespace Ls\Webhooks\Model\Notification;

use \Ls\Webhooks\Api\Data\ReceiverInterface;
use Magento\Framework\DataObject;

class Receiver extends DataObject implements ReceiverInterface
{
    /**
     * @inheritDoc
     */
    public function setReceiverName($receiverName)
    {
        $this->setData(self::RECEIVER_NAME, $receiverName);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReceiverName()
    {
        return $this->getData(self::RECEIVER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setReceiverEmail($receiverEmail)
    {
        $this->setData(self::RECEIVER_EMAIL, $receiverEmail);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReceiverEmail()
    {
        return $this->getData(self::RECEIVER_EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setReceiverPhone($receiverPhone)
    {
        $this->setData(self::RECEIVER_PHONE, $receiverPhone);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReceiverPhone()
    {
        return $this->getData(self::RECEIVER_PHONE);
    }

    /**
     * @inheritDoc
     */
    public function setReceiverRole($receiverRole)
    {
        $this->setData(self::RECEIVER_ROLE, $receiverRole);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReceiverRole()
    {
        return $this->getData(self::RECEIVER_ROLE);
    }

    /**
     * @inheritDoc
     */
    public function setReceiverAddress($receiverAddress)
    {
        $this->setData(self::RECEIVER_ADDRESS, $receiverAddress);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReceiverAddress()
    {
        return $this->getData(self::RECEIVER_ADDRESS);
    }
}
