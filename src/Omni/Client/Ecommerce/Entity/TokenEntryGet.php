<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class TokenEntryGet implements RequestInterface
{
    /**
     * @property string $accountNo
     */
    protected $accountNo = null;

    /**
     * @property boolean $hotelToken
     */
    protected $hotelToken = null;

    /**
     * @param string $accountNo
     * @return $this
     */
    public function setAccountNo($accountNo)
    {
        $this->accountNo = $accountNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccountNo()
    {
        return $this->accountNo;
    }

    /**
     * @param boolean $hotelToken
     * @return $this
     */
    public function setHotelToken($hotelToken)
    {
        $this->hotelToken = $hotelToken;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getHotelToken()
    {
        return $this->hotelToken;
    }
}

