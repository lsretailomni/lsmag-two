<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ClientTokenResult
{

    /**
     * @property string $ExpireDate
     */
    protected $ExpireDate = null;

    /**
     * @property string $Result
     */
    protected $Result = null;

    /**
     * @property string $Token
     */
    protected $Token = null;

    /**
     * @param string $ExpireDate
     * @return $this
     */
    public function setExpireDate($ExpireDate)
    {
        $this->ExpireDate = $ExpireDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpireDate()
    {
        return $this->ExpireDate;
    }

    /**
     * @param string $Result
     * @return $this
     */
    public function setResult($Result)
    {
        $this->Result = $Result;
        return $this;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->Result;
    }

    /**
     * @param string $Token
     * @return $this
     */
    public function setToken($Token)
    {
        $this->Token = $Token;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->Token;
    }


}

