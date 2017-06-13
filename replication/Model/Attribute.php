<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\AttributeInterface;

class Attribute extends AbstractModel implements AttributeInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_attribute';

    protected $_cacheTag = 'lsr_replication_attribute';

    protected $_eventPrefix = 'lsr_replication_attribute';

    protected $Code = null;

    protected $DefaultValue = null;

    protected $Description = null;

    protected $LinkField1 = null;

    protected $LinkField2 = null;

    protected $LinkField3 = null;

    protected $LinkType = null;

    protected $NumbericValue = null;

    protected $OptionValues = null;

    protected $Sequence = null;

    protected $Value = null;

    protected $ValueType = null;

    protected $IsDeleted = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Attribute' );
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

    public function setDefaultValue($DefaultValue)
    {
        $this->DefaultValue = $DefaultValue;
        return $this;
    }

    public function getDefaultValue()
    {
        return $this->DefaultValue;
    }

    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    public function getDescription()
    {
        return $this->Description;
    }

    public function setLinkField1($LinkField1)
    {
        $this->LinkField1 = $LinkField1;
        return $this;
    }

    public function getLinkField1()
    {
        return $this->LinkField1;
    }

    public function setLinkField2($LinkField2)
    {
        $this->LinkField2 = $LinkField2;
        return $this;
    }

    public function getLinkField2()
    {
        return $this->LinkField2;
    }

    public function setLinkField3($LinkField3)
    {
        $this->LinkField3 = $LinkField3;
        return $this;
    }

    public function getLinkField3()
    {
        return $this->LinkField3;
    }

    public function setLinkType($LinkType)
    {
        $this->LinkType = $LinkType;
        return $this;
    }

    public function getLinkType()
    {
        return $this->LinkType;
    }

    public function setNumbericValue($NumbericValue)
    {
        $this->NumbericValue = $NumbericValue;
        return $this;
    }

    public function getNumbericValue()
    {
        return $this->NumbericValue;
    }

    public function setOptionValues($OptionValues)
    {
        $this->OptionValues = $OptionValues;
        return $this;
    }

    public function getOptionValues()
    {
        return $this->OptionValues;
    }

    public function setSequence($Sequence)
    {
        $this->Sequence = $Sequence;
        return $this;
    }

    public function getSequence()
    {
        return $this->Sequence;
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

    public function setValueType($ValueType)
    {
        $this->ValueType = $ValueType;
        return $this;
    }

    public function getValueType()
    {
        return $this->ValueType;
    }

    public function setIsDeleted($IsDeleted)
    {
        $this->IsDeleted = $IsDeleted;
        return $this;
    }

    public function getIsDeleted()
    {
        return $this->IsDeleted;
    }


}

