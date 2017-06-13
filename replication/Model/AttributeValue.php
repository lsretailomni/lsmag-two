<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\AttributeValueInterface;

class AttributeValue extends AbstractModel implements AttributeValueInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_attribute_value';

    protected $_cacheTag = 'lsr_replication_attribute_value';

    protected $_eventPrefix = 'lsr_replication_attribute_value';

    protected $Code = null;

    protected $IsDeleted = null;

    protected $LinkField1 = null;

    protected $LinkField2 = null;

    protected $LinkField3 = null;

    protected $LinkType = null;

    protected $NumbericValue = null;

    protected $Sequence = null;

    protected $Value = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\AttributeValue' );
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

    public function setIsDeleted($IsDeleted)
    {
        $this->IsDeleted = $IsDeleted;
        return $this;
    }

    public function getIsDeleted()
    {
        return $this->IsDeleted;
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


}

