<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class SecurityCheckProfileResponse implements ResponseInterface
{
    /**
     * @property boolean $SecurityCheckProfileResult
     */
    protected $SecurityCheckProfileResult = null;

    /**
     * @param boolean $SecurityCheckProfileResult
     * @return $this
     */
    public function setSecurityCheckProfileResult($SecurityCheckProfileResult)
    {
        $this->SecurityCheckProfileResult = $SecurityCheckProfileResult;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSecurityCheckProfileResult()
    {
        return $this->SecurityCheckProfileResult;
    }

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->SecurityCheckProfileResult;
    }
}

