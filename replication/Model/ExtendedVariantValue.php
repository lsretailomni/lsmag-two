<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ExtendedVariantValueInterface;

class ExtendedVariantValue extends AbstractModel implements ExtendedVariantValueInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_extended_variant_value';

    protected $_cacheTag = 'lsr_replication_extended_variant_value';

    protected $_eventPrefix = 'lsr_replication_extended_variant_value';

    protected $Code = null;

    protected $Del = null;

    protected $Dimensions = null;

    protected $FrameworkCode = null;

    protected $ItemId = null;

    protected $Order = null;

    protected $Timestamp = null;

    protected $Value = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ExtendedVariantValue' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    public function setCode($Code)
    {
        $this->Code = $Code;
        return $this;
    }

    public function getCode()
    {
        return $this->Code;
    }

    public function setDel($Del)
    {
        $this->Del = $Del;
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    public function setDimensions($Dimensions)
    {
        $this->Dimensions = $Dimensions;
        return $this;
    }

    public function getDimensions()
    {
        return $this->Dimensions;
    }

    public function setFrameworkCode($FrameworkCode)
    {
        $this->FrameworkCode = $FrameworkCode;
        return $this;
    }

    public function getFrameworkCode()
    {
        return $this->FrameworkCode;
    }

    public function setItemId($ItemId)
    {
        $this->ItemId = $ItemId;
        return $this;
    }

    public function getItemId()
    {
        return $this->ItemId;
    }

    public function setOrder($Order)
    {
        $this->Order = $Order;
        return $this;
    }

    public function getOrder()
    {
        return $this->Order;
    }

    public function setTimestamp($Timestamp)
    {
        $this->Timestamp = $Timestamp;
        return $this;
    }

    public function getTimestamp()
    {
        return $this->Timestamp;
    }

    public function setValue($Value)
    {
        $this->Value = $Value;
        return $this;
    }

    public function getValue()
    {
        return $this->Value;
    }


}

