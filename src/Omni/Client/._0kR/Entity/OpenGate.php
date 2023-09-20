<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class OpenGate implements RequestInterface
{
    /**
     * @property string $qrCode
     */
    protected $qrCode = null;

    /**
     * @property string $storeNo
     */
    protected $storeNo = null;

    /**
     * @property string $devLocation
     */
    protected $devLocation = null;

    /**
     * @property string $memberAccount
     */
    protected $memberAccount = null;

    /**
     * @property boolean $exitWithoutShopping
     */
    protected $exitWithoutShopping = null;

    /**
     * @property boolean $isEntering
     */
    protected $isEntering = null;

    /**
     * @param string $qrCode
     * @return $this
     */
    public function setQrCode($qrCode)
    {
        $this->qrCode = $qrCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getQrCode()
    {
        return $this->qrCode;
    }

    /**
     * @param string $storeNo
     * @return $this
     */
    public function setStoreNo($storeNo)
    {
        $this->storeNo = $storeNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreNo()
    {
        return $this->storeNo;
    }

    /**
     * @param string $devLocation
     * @return $this
     */
    public function setDevLocation($devLocation)
    {
        $this->devLocation = $devLocation;
        return $this;
    }

    /**
     * @return string
     */
    public function getDevLocation()
    {
        return $this->devLocation;
    }

    /**
     * @param string $memberAccount
     * @return $this
     */
    public function setMemberAccount($memberAccount)
    {
        $this->memberAccount = $memberAccount;
        return $this;
    }

    /**
     * @return string
     */
    public function getMemberAccount()
    {
        return $this->memberAccount;
    }

    /**
     * @param boolean $exitWithoutShopping
     * @return $this
     */
    public function setExitWithoutShopping($exitWithoutShopping)
    {
        $this->exitWithoutShopping = $exitWithoutShopping;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getExitWithoutShopping()
    {
        return $this->exitWithoutShopping;
    }

    /**
     * @param boolean $isEntering
     * @return $this
     */
    public function setIsEntering($isEntering)
    {
        $this->isEntering = $isEntering;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsEntering()
    {
        return $this->isEntering;
    }
}
