<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class MenuNode extends Entity
{
    /**
     * @property ArrayOfMenuNode $MenuGroupNodes
     */
    protected $MenuGroupNodes = null;

    /**
     * @property ArrayOfMenuNodeLine $MenuNodeLines
     */
    protected $MenuNodeLines = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property int $DisplayOrder
     */
    protected $DisplayOrder = null;

    /**
     * @property ImageView $Image
     */
    protected $Image = null;

    /**
     * @property boolean $NodeIsItem
     */
    protected $NodeIsItem = null;

    /**
     * @property string $PriceGroup
     */
    protected $PriceGroup = null;

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
     * @param ArrayOfMenuNode $MenuGroupNodes
     * @return $this
     */
    public function setMenuGroupNodes($MenuGroupNodes)
    {
        $this->MenuGroupNodes = $MenuGroupNodes;
        return $this;
    }

    /**
     * @return ArrayOfMenuNode
     */
    public function getMenuGroupNodes()
    {
        return $this->MenuGroupNodes;
    }

    /**
     * @param ArrayOfMenuNodeLine $MenuNodeLines
     * @return $this
     */
    public function setMenuNodeLines($MenuNodeLines)
    {
        $this->MenuNodeLines = $MenuNodeLines;
        return $this;
    }

    /**
     * @return ArrayOfMenuNodeLine
     */
    public function getMenuNodeLines()
    {
        return $this->MenuNodeLines;
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
     * @param boolean $NodeIsItem
     * @return $this
     */
    public function setNodeIsItem($NodeIsItem)
    {
        $this->NodeIsItem = $NodeIsItem;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getNodeIsItem()
    {
        return $this->NodeIsItem;
    }

    /**
     * @param string $PriceGroup
     * @return $this
     */
    public function setPriceGroup($PriceGroup)
    {
        $this->PriceGroup = $PriceGroup;
        return $this;
    }

    /**
     * @return string
     */
    public function getPriceGroup()
    {
        return $this->PriceGroup;
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
}

