<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class NotificationsGetByCardIdResponse implements ResponseInterface
{
    /**
     * @property ArrayOfNotification $NotificationsGetByCardIdResult
     */
    protected $NotificationsGetByCardIdResult = null;

    /**
     * @param ArrayOfNotification $NotificationsGetByCardIdResult
     * @return $this
     */
    public function setNotificationsGetByCardIdResult($NotificationsGetByCardIdResult)
    {
        $this->NotificationsGetByCardIdResult = $NotificationsGetByCardIdResult;
        return $this;
    }

    /**
     * @return ArrayOfNotification
     */
    public function getNotificationsGetByCardIdResult()
    {
        return $this->NotificationsGetByCardIdResult;
    }

    /**
     * @return ArrayOfNotification
     */
    public function getResult()
    {
        return $this->NotificationsGetByCardIdResult;
    }
}

