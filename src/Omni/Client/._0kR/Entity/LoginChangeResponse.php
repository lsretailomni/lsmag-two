<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class LoginChangeResponse implements ResponseInterface
{
    /**
     * @property boolean $LoginChangeResult
     */
    protected $LoginChangeResult = null;

    /**
     * @param boolean $LoginChangeResult
     * @return $this
     */
    public function setLoginChangeResult($LoginChangeResult)
    {
        $this->LoginChangeResult = $LoginChangeResult;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getLoginChangeResult()
    {
        return $this->LoginChangeResult;
    }

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->LoginChangeResult;
    }
}

