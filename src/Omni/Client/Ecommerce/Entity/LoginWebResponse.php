<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class LoginWebResponse implements ResponseInterface
{
    /**
     * @property MemberContact $LoginWebResult
     */
    protected $LoginWebResult = null;

    /**
     * @param MemberContact $LoginWebResult
     * @return $this
     */
    public function setLoginWebResult($LoginWebResult)
    {
        $this->LoginWebResult = $LoginWebResult;
        return $this;
    }

    /**
     * @return MemberContact
     */
    public function getLoginWebResult()
    {
        return $this->LoginWebResult;
    }

    /**
     * @return MemberContact
     */
    public function getResult()
    {
        return $this->LoginWebResult;
    }
}

