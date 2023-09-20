<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ReplExtendedVariantValue
{
    /**
     * @property string $Code
     */
    protected $Code = null;

    /**
     * @property int $DimensionLogicalOrder
     */
    protected $DimensionLogicalOrder = null;

    /**
     * @property string $Dimensions
     */
    protected $Dimensions = null;

    /**
     * @property string $FrameworkCode
     */
    protected $FrameworkCode = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property string $ItemId
     */
    protected $ItemId = null;

    /**
     * @property int $LogicalOrder
     */
    protected $LogicalOrder = null;

    /**
     * @property string $Timestamp
     */
    protected $Timestamp = null;

    /**
     * @property string $Value
     */
    protected $Value = null;

    /**
     * @property string $scope
     */
    protected $scope = null;

    /**
     * @property int $scope_id
     */
    protected $scope_id = null;

    /**
     * @param string $Code
     * @return $this
     */
    public function setCode($Code)
    {
        $this->Code = $Code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->Code;
    }

    /**
     * @param int $DimensionLogicalOrder
     * @return $this
     */
    public function setDimensionLogicalOrder($DimensionLogicalOrder)
    {
        $this->DimensionLogicalOrder = $DimensionLogicalOrder;
        return $this;
    }

    /**
     * @return int
     */
    public function getDimensionLogicalOrder()
    {
        return $this->DimensionLogicalOrder;
    }

    /**
     * @param string $Dimensions
     * @return $this
     */
    public function setDimensions($Dimensions)
    {
        $this->Dimensions = $Dimensions;
        return $this;
    }

    /**
     * @return string
     */
    public function getDimensions()
    {
        return $this->Dimensions;
    }

    /**
     * @param string $FrameworkCode
     * @return $this
     */
    public function setFrameworkCode($FrameworkCode)
    {
        $this->FrameworkCode = $FrameworkCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrameworkCode()
    {
        return $this->FrameworkCode;
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
     * @param int $LogicalOrder
     * @return $this
     */
    public function setLogicalOrder($LogicalOrder)
    {
        $this->LogicalOrder = $LogicalOrder;
        return $this;
    }

    /**
     * @return int
     */
    public function getLogicalOrder()
    {
        return $this->LogicalOrder;
    }

    /**
     * @param string $Timestamp
     * @return $this
     */
    public function setTimestamp($Timestamp)
    {
        $this->Timestamp = $Timestamp;
        return $this;
    }

    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->Timestamp;
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
