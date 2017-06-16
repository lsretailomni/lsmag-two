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

    /**
     * @return $this
     */
    public function setCode($Code)
    {
        $this->setData( 'Code', $Code );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCode()
    {
        return $this->Code;
    }

    /**
     * @return $this
     */
    public function setIsDeleted($IsDeleted)
    {
        $this->setData( 'IsDeleted', $IsDeleted );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getIsDeleted()
    {
        return $this->IsDeleted;
    }

    /**
     * @return $this
     */
    public function setLinkField1($LinkField1)
    {
        $this->setData( 'LinkField1', $LinkField1 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getLinkField1()
    {
        return $this->LinkField1;
    }

    /**
     * @return $this
     */
    public function setLinkField2($LinkField2)
    {
        $this->setData( 'LinkField2', $LinkField2 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getLinkField2()
    {
        return $this->LinkField2;
    }

    /**
     * @return $this
     */
    public function setLinkField3($LinkField3)
    {
        $this->setData( 'LinkField3', $LinkField3 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getLinkField3()
    {
        return $this->LinkField3;
    }

    /**
     * @return $this
     */
    public function setLinkType($LinkType)
    {
        $this->setData( 'LinkType', $LinkType );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getLinkType()
    {
        return $this->LinkType;
    }

    /**
     * @return $this
     */
    public function setNumbericValue($NumbericValue)
    {
        $this->setData( 'NumbericValue', $NumbericValue );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getNumbericValue()
    {
        return $this->NumbericValue;
    }

    /**
     * @return $this
     */
    public function setSequence($Sequence)
    {
        $this->setData( 'Sequence', $Sequence );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getSequence()
    {
        return $this->Sequence;
    }

    /**
     * @return $this
     */
    public function setValue($Value)
    {
        $this->setData( 'Value', $Value );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getValue()
    {
        return $this->Value;
    }


}

