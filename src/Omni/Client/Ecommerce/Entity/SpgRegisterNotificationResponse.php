<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class SpgRegisterNotificationResponse implements ResponseInterface
{
    /**
     * @property boolean $SpgRegisterNotificationResult
     */
    protected $SpgRegisterNotificationResult = null;

    /**
     * @param boolean $SpgRegisterNotificationResult
     * @return $this
     */
    public function setSpgRegisterNotificationResult($SpgRegisterNotificationResult)
    {
        $this->SpgRegisterNotificationResult = $SpgRegisterNotificationResult;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSpgRegisterNotificationResult()
    {
        return $this->SpgRegisterNotificationResult;
    }

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->SpgRegisterNotificationResult;
    }
}

