<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class OrderLineStatus
{
    /**
     * @property boolean $AllowCancel
     */
    protected $AllowCancel = null;

    /**
     * @property boolean $AllowModify
     */
    protected $AllowModify = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $ExtCode
     */
    protected $ExtCode = null;

    /**
     * @property string $ItemId
     */
    protected $ItemId = null;

    /**
     * @property int $LineNumber
     */
    protected $LineNumber = null;

    /**
     * @property string $LineStatus
     */
    protected $LineStatus = null;

    /**
     * @property float $Quantity
     */
    protected $Quantity = null;

    /**
     * @property string $UnitOfMeasureId
     */
    protected $UnitOfMeasureId = null;

    /**
     * @property string $VariantId
     */
    protected $VariantId = null;

    /**
     * @param boolean $AllowCancel
     * @return $this
     */
    public function setAllowCancel($AllowCancel)
    {
        $this->AllowCancel = $AllowCancel;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAllowCancel()
    {
        return $this->AllowCancel;
    }

    /**
     * @param boolean $AllowModify
     * @return $this
     */
    public function setAllowModify($AllowModify)
    {
        $this->AllowModify = $AllowModify;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAllowModify()
    {
        return $this->AllowModify;
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
     * @param string $ExtCode
     * @return $this
     */
    public function setExtCode($ExtCode)
    {
        $this->ExtCode = $ExtCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtCode()
    {
        return $this->ExtCode;
    }

    /**
     * @param string $ItemId
     * @return $this
     */
    public function setItemId($ItemId)
    {
        $this->ItemId = $ItemId;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->ItemId;
    }

    /**
     * @param int $LineNumber
     * @return $this
     */
    public function setLineNumber($LineNumber)
    {
        $this->LineNumber = $LineNumber;
        return $this;
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->LineNumber;
    }

    /**
     * @param string $LineStatus
     * @return $this
     */
    public function setLineStatus($LineStatus)
    {
        $this->LineStatus = $LineStatus;
        return $this;
    }

    /**
     * @return string
     */
    public function getLineStatus()
    {
        return $this->LineStatus;
    }

    /**
     * @param float $Quantity
     * @return $this
     */
    public function setQuantity($Quantity)
    {
        $this->Quantity = $Quantity;
        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->Quantity;
    }

    /**
     * @param string $UnitOfMeasureId
     * @return $this
     */
    public function setUnitOfMeasureId($UnitOfMeasureId)
    {
        $this->UnitOfMeasureId = $UnitOfMeasureId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnitOfMeasureId()
    {
        return $this->UnitOfMeasureId;
    }

    /**
     * @param string $VariantId
     * @return $this
     */
    public function setVariantId($VariantId)
    {
        $this->VariantId = $VariantId;
        return $this;
    }

    /**
     * @return string
     */
    public function getVariantId()
    {
        return $this->VariantId;
    }
}

