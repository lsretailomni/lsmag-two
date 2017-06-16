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
    public function setDefaultValue($DefaultValue)
    {
        $this->setData( 'DefaultValue', $DefaultValue );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDefaultValue()
    {
        return $this->DefaultValue;
    }

    /**
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->setData( 'Description', $Description );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDescription()
    {
        return $this->Description;
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
    public function setOptionValues($OptionValues)
    {
        $this->setData( 'OptionValues', $OptionValues );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getOptionValues()
    {
        return $this->OptionValues;
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

    /**
     * @return $this
     */
    public function setValueType($ValueType)
    {
        $this->setData( 'ValueType', $ValueType );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getValueType()
    {
        return $this->ValueType;
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


}

