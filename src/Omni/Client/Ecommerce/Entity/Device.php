<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class Device extends Entity
{
    /**
     * @property string $BlockedBy
     */
    protected $BlockedBy = null;

    /**
     * @property string $BlockedDate
     */
    protected $BlockedDate = null;

    /**
     * @property string $BlockedReason
     */
    protected $BlockedReason = null;

    /**
     * @property string $CardId
     */
    protected $CardId = null;

    /**
     * @property string $DeviceFriendlyName
     */
    protected $DeviceFriendlyName = null;

    /**
     * @property string $Manufacturer
     */
    protected $Manufacturer = null;

    /**
     * @property string $Model
     */
    protected $Model = null;

    /**
     * @property string $OsVersion
     */
    protected $OsVersion = null;

    /**
     * @property string $Platform
     */
    protected $Platform = null;

    /**
     * @property string $SecurityToken
     */
    protected $SecurityToken = null;

    /**
     * @property int $Status
     */
    protected $Status = null;

    /**
     * @param string $BlockedBy
     * @return $this
     */
    public function setBlockedBy($BlockedBy)
    {
        $this->BlockedBy = $BlockedBy;
        return $this;
    }

    /**
     * @return string
     */
    public function getBlockedBy()
    {
        return $this->BlockedBy;
    }

    /**
     * @param string $BlockedDate
     * @return $this
     */
    public function setBlockedDate($BlockedDate)
    {
        $this->BlockedDate = $BlockedDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getBlockedDate()
    {
        return $this->BlockedDate;
    }

    /**
     * @param string $BlockedReason
     * @return $this
     */
    public function setBlockedReason($BlockedReason)
    {
        $this->BlockedReason = $BlockedReason;
        return $this;
    }

    /**
     * @return string
     */
    public function getBlockedReason()
    {
        return $this->BlockedReason;
    }

    /**
     * @param string $CardId
     * @return $this
     */
    public function setCardId($CardId)
    {
        $this->CardId = $CardId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardId()
    {
        return $this->CardId;
    }

    /**
     * @param string $DeviceFriendlyName
     * @return $this
     */
    public function setDeviceFriendlyName($DeviceFriendlyName)
    {
        $this->DeviceFriendlyName = $DeviceFriendlyName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeviceFriendlyName()
    {
        return $this->DeviceFriendlyName;
    }

    /**
     * @param string $Manufacturer
     * @return $this
     */
    public function setManufacturer($Manufacturer)
    {
        $this->Manufacturer = $Manufacturer;
        return $this;
    }

    /**
     * @return string
     */
    public function getManufacturer()
    {
        return $this->Manufacturer;
    }

    /**
     * @param string $Model
     * @return $this
     */
    public function setModel($Model)
    {
        $this->Model = $Model;
        return $this;
    }

    /**
     * @return string
     */
    public function getModel()
    {
        return $this->Model;
    }

    /**
     * @param string $OsVersion
     * @return $this
     */
    public function setOsVersion($OsVersion)
    {
        $this->OsVersion = $OsVersion;
        return $this;
    }

    /**
     * @return string
     */
    public function getOsVersion()
    {
        return $this->OsVersion;
    }

    /**
     * @param string $Platform
     * @return $this
     */
    public function setPlatform($Platform)
    {
        $this->Platform = $Platform;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->Platform;
    }

    /**
     * @param string $SecurityToken
     * @return $this
     */
    public function setSecurityToken($SecurityToken)
    {
        $this->SecurityToken = $SecurityToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecurityToken()
    {
        return $this->SecurityToken;
    }

    /**
     * @param int $Status
     * @return $this
     */
    public function setStatus($Status)
    {
        $this->Status = $Status;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->Status;
    }
}

