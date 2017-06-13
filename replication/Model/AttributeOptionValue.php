<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\AttributeOptionValueInterface;

class AttributeOptionValue extends AbstractModel implements AttributeOptionValueInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_attribute_option_value';

    protected $_cacheTag = 'lsr_replication_attribute_option_value';

    protected $_eventPrefix = 'lsr_replication_attribute_option_value';

    protected $Code = null;

    protected $Sequence = null;

    protected $Value = null;

    protected $IsDeleted = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\AttributeOptionValue' );
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

