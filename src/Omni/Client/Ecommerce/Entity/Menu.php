<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class Menu extends Entity
{
    /**
     * @property ArrayOfMenuNode $MenuNodes
     */
    protected $MenuNodes = null;

    /**
     * @property boolean $DefaultMenu
     */
    protected $DefaultMenu = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property ImageView $Image
     */
    protected $Image = null;

    /**
     * @property int $OrderSequenceNumber
     */
    protected $OrderSequenceNumber = null;

    /**
     * @property string $ValidDescription
     */
    protected $ValidDescription = null;

    /**
     * @property string $ValidationEndTime
     */
    protected $ValidationEndTime = null;

    /**
     * @property boolean $ValidationEndTimeAfterMidnight
     */
    protected $ValidationEndTimeAfterMidnight = null;

    /**
     * @property string $ValidationStartTime
     */
    protected $ValidationStartTime = null;

    /**
     * @property boolean $ValidationTimeWithinBounds
     */
    protected $ValidationTimeWithinBounds = null;

    /**
     * @property string $Version
     */
    protected $Version = null;

    /**
     * @param ArrayOfMenuNode $MenuNodes
     * @return $this
     */
    public function setMenuNodes($MenuNodes)
    {
        $this->MenuNodes = $MenuNodes;
        return $this;
    }

    /**
     * @return ArrayOfMenuNode
     */
    public function getMenuNodes()
    {
        return $this->MenuNodes;
    }

    /**
     * @param boolean $DefaultMenu
     * @return $this
     */
    public function setDefaultMenu($DefaultMenu)
    {
        $this->DefaultMenu = $DefaultMenu;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDefaultMenu()
    {
        return $this->DefaultMenu;
    }

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
     * @param ImageView $Image
     * @return $this
     */
    public function setImage($Image)
    {
        $this->Image = $Image;
        return $this;
    }

    /**
     * @return ImageView
     */
    public function getImage()
    {
        return $this->Image;
    }

    /**
     * @param int $OrderSequenceNumber
     * @return $this
     */
    public function setOrderSequenceNumber($OrderSequenceNumber)
    {
        $this->OrderSequenceNumber = $OrderSequenceNumber;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrderSequenceNumber()
    {
        return $this->OrderSequenceNumber;
    }

    /**
     * @param string $ValidDescription
     * @return $this
     */
    public function setValidDescription($ValidDescription)
    {
        $this->ValidDescription = $ValidDescription;
        return $this;
    }

    /**
     * @return string
     */
    public function getValidDescription()
    {
        return $this->ValidDescription;
    }

    /**
     * @param string $ValidationEndTime
     * @return $this
     */
    public function setValidationEndTime($ValidationEndTime)
    {
        $this->ValidationEndTime = $ValidationEndTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getValidationEndTime()
    {
        return $this->ValidationEndTime;
    }

    /**
     * @param boolean $ValidationEndTimeAfterMidnight
     * @return $this
     */
    public function setValidationEndTimeAfterMidnight($ValidationEndTimeAfterMidnight)
    {
        $this->ValidationEndTimeAfterMidnight = $ValidationEndTimeAfterMidnight;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getValidationEndTimeAfterMidnight()
    {
        return $this->ValidationEndTimeAfterMidnight;
    }

    /**
     * @param string $ValidationStartTime
     * @return $this
     */
    public function setValidationStartTime($ValidationStartTime)
    {
        $this->ValidationStartTime = $ValidationStartTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getValidationStartTime()
    {
        return $this->ValidationStartTime;
    }

    /**
     * @param boolean $ValidationTimeWithinBounds
     * @return $this
     */
    public function setValidationTimeWithinBounds($ValidationTimeWithinBounds)
    {
        $this->ValidationTimeWithinBounds = $ValidationTimeWithinBounds;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getValidationTimeWithinBounds()
    {
        return $this->ValidationTimeWithinBounds;
    }

    /**
     * @param string $Version
     * @return $this
     */
    public function setVersion($Version)
    {
        $this->Version = $Version;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->Version;
    }
}

