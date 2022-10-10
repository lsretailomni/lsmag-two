<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Notification;

use \Ls\Webhooks\Api\Data\SenderInterface;
use Magento\Framework\DataObject;

class Sender extends DataObject implements SenderInterface
{
    /**
     * @inheritDoc
     */
    public function setSenderName($senderName)
    {
        $this->setData(self::SENDER_NAME, $senderName);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSenderName()
    {
        return $this->getData(self::SENDER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setSenderEmail($senderEmail)
    {
        $this->setData(self::SENDER_EMAIL, $senderEmail);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSenderEmail()
    {
        return $this->getData(self::SENDER_EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setSenderPhone($senderPhone)
    {
        $this->setData(self::SENDER_PHONE, $senderPhone);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSenderPhone()
    {
        return $this->getData(self::SENDER_PHONE);
    }

    /**
     * @inheritDoc
     */
    public function setSenderRole($senderRole)
    {
        $this->setData(self::SENDER_ROLE, $senderRole);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSenderRole()
    {
        return $this->getData(self::SENDER_ROLE);
    }

    /**
     * @inheritDoc
     */
    public function setSenderAddress($senderAddress)
    {
        $this->setData(self::SENDER_ADDRESS, $senderAddress);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSenderAddress()
    {
        return $this->getData(self::SENDER_ADDRESS);
    }
}
