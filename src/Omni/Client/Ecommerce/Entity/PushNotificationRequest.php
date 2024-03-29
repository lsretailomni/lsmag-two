<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\PushApplication;
use Ls\Omni\Client\Ecommerce\Entity\Enum\PushPlatform;
use Ls\Omni\Client\Ecommerce\Entity\Enum\PushStatus;
use Ls\Omni\Exception\InvalidEnumException;

class PushNotificationRequest
{
    /**
     * @property PushApplication $Application
     */
    protected $Application = null;

    /**
     * @property string $Body
     */
    protected $Body = null;

    /**
     * @property string $DeviceId
     */
    protected $DeviceId = null;

    /**
     * @property string $Id
     */
    protected $Id = null;

    /**
     * @property PushPlatform $Platform
     */
    protected $Platform = null;

    /**
     * @property PushStatus $Status
     */
    protected $Status = null;

    /**
     * @property string $Title
     */
    protected $Title = null;

    /**
     * @param PushApplication|string $Application
     * @return $this
     * @throws InvalidEnumException
     */
    public function setApplication($Application)
    {
        if ( ! $Application instanceof PushApplication ) {
            if ( PushApplication::isValid( $Application ) )
                $Application = new PushApplication( $Application );
            elseif ( PushApplication::isValidKey( $Application ) )
                $Application = new PushApplication( constant( "PushApplication::$Application" ) );
            elseif ( ! $Application instanceof PushApplication )
                throw new InvalidEnumException();
        }
        $this->Application = $Application->getValue();

        return $this;
    }

    /**
     * @return PushApplication
     */
    public function getApplication()
    {
        return $this->Application;
    }

    /**
     * @param string $Body
     * @return $this
     */
    public function setBody($Body)
    {
        $this->Body = $Body;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->Body;
    }

    /**
     * @param string $DeviceId
     * @return $this
     */
    public function setDeviceId($DeviceId)
    {
        $this->DeviceId = $DeviceId;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeviceId()
    {
        return $this->DeviceId;
    }

    /**
     * @param string $Id
     * @return $this
     */
    public function setId($Id)
    {
        $this->Id = $Id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->Id;
    }

    /**
     * @param PushPlatform|string $Platform
     * @return $this
     * @throws InvalidEnumException
     */
    public function setPlatform($Platform)
    {
        if ( ! $Platform instanceof PushPlatform ) {
            if ( PushPlatform::isValid( $Platform ) )
                $Platform = new PushPlatform( $Platform );
            elseif ( PushPlatform::isValidKey( $Platform ) )
                $Platform = new PushPlatform( constant( "PushPlatform::$Platform" ) );
            elseif ( ! $Platform instanceof PushPlatform )
                throw new InvalidEnumException();
        }
        $this->Platform = $Platform->getValue();

        return $this;
    }

    /**
     * @return PushPlatform
     */
    public function getPlatform()
    {
        return $this->Platform;
    }

    /**
     * @param PushStatus|string $Status
     * @return $this
     * @throws InvalidEnumException
     */
    public function setStatus($Status)
    {
        if ( ! $Status instanceof PushStatus ) {
            if ( PushStatus::isValid( $Status ) )
                $Status = new PushStatus( $Status );
            elseif ( PushStatus::isValidKey( $Status ) )
                $Status = new PushStatus( constant( "PushStatus::$Status" ) );
            elseif ( ! $Status instanceof PushStatus )
                throw new InvalidEnumException();
        }
        $this->Status = $Status->getValue();

        return $this;
    }

    /**
     * @return PushStatus
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * @param string $Title
     * @return $this
     */
    public function setTitle($Title)
    {
        $this->Title = $Title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->Title;
    }
}

