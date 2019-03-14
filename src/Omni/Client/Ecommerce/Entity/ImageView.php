<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\LocationType;
use Ls\Omni\Exception\InvalidEnumException;

class ImageView extends Entity
{

    /**
     * @property string $AvgColor
     */
    protected $AvgColor = null;

    /**
     * @property int $DisplayOrder
     */
    protected $DisplayOrder = null;

    /**
     * @property string $Format
     */
    protected $Format = null;

    /**
     * @property string $Image
     */
    protected $Image = null;

    /**
     * @property ImageSize $ImgSize
     */
    protected $ImgSize = null;

    /**
     * @property boolean $LoadFromFile
     */
    protected $LoadFromFile = null;

    /**
     * @property string $Location
     */
    protected $Location = null;

    /**
     * @property LocationType $LocationType
     */
    protected $LocationType = null;

    /**
     * @property string $ObjectId
     */
    protected $ObjectId = null;

    /**
     * @param string $AvgColor
     * @return $this
     */
    public function setAvgColor($AvgColor)
    {
        $this->AvgColor = $AvgColor;
        return $this;
    }

    /**
     * @return string
     */
    public function getAvgColor()
    {
        return $this->AvgColor;
    }

    /**
     * @param int $DisplayOrder
     * @return $this
     */
    public function setDisplayOrder($DisplayOrder)
    {
        $this->DisplayOrder = $DisplayOrder;
        return $this;
    }

    /**
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->DisplayOrder;
    }

    /**
     * @param string $Format
     * @return $this
     */
    public function setFormat($Format)
    {
        $this->Format = $Format;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->Format;
    }

    /**
     * @param string $Image
     * @return $this
     */
    public function setImage($Image)
    {
        $this->Image = $Image;
        return $this;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->Image;
    }

    /**
     * @param ImageSize $ImgSize
     * @return $this
     */
    public function setImgSize($ImgSize)
    {
        $this->ImgSize = $ImgSize;
        return $this;
    }

    /**
     * @return ImageSize
     */
    public function getImgSize()
    {
        return $this->ImgSize;
    }

    /**
     * @param boolean $LoadFromFile
     * @return $this
     */
    public function setLoadFromFile($LoadFromFile)
    {
        $this->LoadFromFile = $LoadFromFile;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getLoadFromFile()
    {
        return $this->LoadFromFile;
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
     * @param string $ObjectId
     * @return $this
     */
    public function setObjectId($ObjectId)
    {
        $this->ObjectId = $ObjectId;
        return $this;
    }

    /**
     * @return string
     */
    public function getObjectId()
    {
        return $this->ObjectId;
    }


}

