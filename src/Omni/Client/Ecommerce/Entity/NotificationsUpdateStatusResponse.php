<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class NotificationsUpdateStatusResponse implements ResponseInterface
{
    /**
     * @property boolean $NotificationsUpdateStatusResult
     */
    protected $NotificationsUpdateStatusResult = null;

    /**
     * @param boolean $NotificationsUpdateStatusResult
     * @return $this
     */
    public function setNotificationsUpdateStatusResult($NotificationsUpdateStatusResult)
    {
        $this->NotificationsUpdateStatusResult = $NotificationsUpdateStatusResult;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getNotificationsUpdateStatusResult()
    {
        return $this->NotificationsUpdateStatusResult;
    }

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->NotificationsUpdateStatusResult;
    }
}

