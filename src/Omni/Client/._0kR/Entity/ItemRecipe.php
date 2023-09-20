<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ItemRecipe extends Entity
{
    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property boolean $Exclusion
     */
    protected $Exclusion = null;

    /**
     * @property float $ExclusionPrice
     */
    protected $ExclusionPrice = null;

    /**
     * @property string $ImageId
     */
    protected $ImageId = null;

    /**
     * @property int $LineNo
     */
    protected $LineNo = null;

    /**
     * @property float $QuantityPer
     */
    protected $QuantityPer = null;

    /**
     * @property string $UnitOfMeasure
     */
    protected $UnitOfMeasure = null;

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
     * @param boolean $Exclusion
     * @return $this
     */
    public function setExclusion($Exclusion)
    {
        $this->Exclusion = $Exclusion;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getExclusion()
    {
        return $this->Exclusion;
    }

    /**
     * @param float $ExclusionPrice
     * @return $this
     */
    public function setExclusionPrice($ExclusionPrice)
    {
        $this->ExclusionPrice = $ExclusionPrice;
        return $this;
    }

    /**
     * @return float
     */
    public function getExclusionPrice()
    {
        return $this->ExclusionPrice;
    }

    /**
     * @param string $ImageId
     * @return $this
     */
    public function setImageId($ImageId)
    {
        $this->ImageId = $ImageId;
        return $this;
    }

    /**
     * @return string
     */
    public function getImageId()
    {
        return $this->ImageId;
    }

    /**
     * @param int $LineNo
     * @return $this
     */
    public function setLineNo($LineNo)
    {
        $this->LineNo = $LineNo;
        return $this;
    }

    /**
     * @return int
     */
    public function getLineNo()
    {
        return $this->LineNo;
    }

    /**
     * @param float $QuantityPer
     * @return $this
     */
    public function setQuantityPer($QuantityPer)
    {
        $this->QuantityPer = $QuantityPer;
        return $this;
    }

    /**
     * @return float
     */
    public function getQuantityPer()
    {
        return $this->QuantityPer;
    }

    /**
     * @param string $UnitOfMeasure
     * @return $this
     */
    public function setUnitOfMeasure($UnitOfMeasure)
    {
        $this->UnitOfMeasure = $UnitOfMeasure;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnitOfMeasure()
    {
        return $this->UnitOfMeasure;
    }
}
