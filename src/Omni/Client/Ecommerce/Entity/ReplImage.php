<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\LocationType;
use Ls\Omni\Exception\InvalidEnumException;

class ReplImage
{
    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $Id
     */
    protected $Id = null;

    /**
     * @property string $Image64
     */
    protected $Image64 = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property string $Location
     */
    protected $Location = null;

    /**
     * @property LocationType $LocationType
     */
    protected $LocationType = null;

    /**
     * @property string $MediaId
     */
    protected $MediaId = null;

    /**
     * @property ImageSize $Size
     */
    protected $Size = null;

    /**
     * @property string $scope
     */
    protected $scope = null;

    /**
     * @property int $scope_id
     */
    protected $scope_id = null;

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
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
     * @param string $Image64
     * @return $this
     */
    public function setImage64($Image64)
    {
        $this->Image64 = $Image64;
        return $this;
    }

    /**
     * @return string
     */
    public function getImage64()
    {
        return $this->Image64;
    }

    /**
     * @param boolean $IsDeleted
     * @return $this
     */
    public function setIsDeleted($IsDeleted)
    {
        $this->IsDeleted = $IsDeleted;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->IsDeleted;
    }

    /**
     * @param string $Location
     * @return $this
     */
    public function setLocation($Location)
    {
        $this->Location = $Location;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->Location;
    }

    /**
     * @param LocationType|string $LocationType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setLocationType($LocationType)
    {
        if ( ! $LocationType instanceof LocationType ) {
            if ( LocationType::isValid( $LocationType ) )
                $LocationType = new LocationType( $LocationType );
            elseif ( LocationType::isValidKey( $LocationType ) )
                $LocationType = new LocationType( constant( "LocationType::$LocationType" ) );
            elseif ( ! $LocationType instanceof LocationType )
                throw new InvalidEnumException();
        }
        $this->LocationType = $LocationType->getValue();

        return $this;
    }

    /**
     * @return LocationType
     */
    public function getLocationType()
    {
        return $this->LocationType;
    }

    /**
     * @param string $MediaId
     * @return $this
     */
    public function setMediaId($MediaId)
    {
        $this->MediaId = $MediaId;
        return $this;
    }

    /**
     * @return string
     */
    public function getMediaId()
    {
        return $this->MediaId;
    }

    /**
     * @param ImageSize $Size
     * @return $this
     */
    public function setSize($Size)
    {
        $this->Size = $Size;
        return $this;
    }

    /**
     * @return ImageSize
     */
    public function getSize()
    {
        return $this->Size;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param int $scope_id
     * @return $this
     */
    public function setScopeId($scope_id)
    {
        $this->scope_id = $scope_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        return $this->scope_id;
    }
}

