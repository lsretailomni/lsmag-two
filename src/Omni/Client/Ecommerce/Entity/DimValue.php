<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class DimValue
{
    /**
     * @property int $DisplayOrder
     */
    protected $DisplayOrder = null;

    /**
     * @property boolean $IsSelected
     */
    protected $IsSelected = null;

    /**
     * @property string $Value
     */
    protected $Value = null;

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
     * @param boolean $IsSelected
     * @return $this
     */
    public function setIsSelected($IsSelected)
    {
        $this->IsSelected = $IsSelected;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsSelected()
    {
        return $this->IsSelected;
    }

    /**
     * @param string $Value
     * @return $this
     */
    public function setValue($Value)
    {
        $this->Value = $Value;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->Value;
    }
}

