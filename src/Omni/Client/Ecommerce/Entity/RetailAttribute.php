<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\AttributeLinkType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\AttributeValueType;
use Ls\Omni\Exception\InvalidEnumException;

class RetailAttribute
{
    /**
     * @property ArrayOfAttributeOptionValue $OptionValues
     */
    protected $OptionValues = null;

    /**
     * @property string $Code
     */
    protected $Code = null;

    /**
     * @property string $DefaultValue
     */
    protected $DefaultValue = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $LinkField1
     */
    protected $LinkField1 = null;

    /**
     * @property string $LinkField2
     */
    protected $LinkField2 = null;

    /**
     * @property string $LinkField3
     */
    protected $LinkField3 = null;

    /**
     * @property AttributeLinkType $LinkType
     */
    protected $LinkType = null;

    /**
     * @property float $NumericValue
     */
    protected $NumericValue = null;

    /**
     * @property int $Sequence
     */
    protected $Sequence = null;

    /**
     * @property string $Value
     */
    protected $Value = null;

    /**
     * @property AttributeValueType $ValueType
     */
    protected $ValueType = null;

    /**
     * @param ArrayOfAttributeOptionValue $OptionValues
     * @return $this
     */
    public function setOptionValues($OptionValues)
    {
        $this->OptionValues = $OptionValues;
        return $this;
    }

    /**
     * @return ArrayOfAttributeOptionValue
     */
    public function getOptionValues()
    {
        return $this->OptionValues;
    }

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
     * @param string $DefaultValue
     * @return $this
     */
    public function setDefaultValue($DefaultValue)
    {
        $this->DefaultValue = $DefaultValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->DefaultValue;
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
     * @param string $LinkField1
     * @return $this
     */
    public function setLinkField1($LinkField1)
    {
        $this->LinkField1 = $LinkField1;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkField1()
    {
        return $this->LinkField1;
    }

    /**
     * @param string $LinkField2
     * @return $this
     */
    public function setLinkField2($LinkField2)
    {
        $this->LinkField2 = $LinkField2;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkField2()
    {
        return $this->LinkField2;
    }

    /**
     * @param string $LinkField3
     * @return $this
     */
    public function setLinkField3($LinkField3)
    {
        $this->LinkField3 = $LinkField3;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkField3()
    {
        return $this->LinkField3;
    }

    /**
     * @param AttributeLinkType|string $LinkType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setLinkType($LinkType)
    {
        if ( ! $LinkType instanceof AttributeLinkType ) {
            if ( AttributeLinkType::isValid( $LinkType ) )
                $LinkType = new AttributeLinkType( $LinkType );
            elseif ( AttributeLinkType::isValidKey( $LinkType ) )
                $LinkType = new AttributeLinkType( constant( "AttributeLinkType::$LinkType" ) );
            elseif ( ! $LinkType instanceof AttributeLinkType )
                throw new InvalidEnumException();
        }
        $this->LinkType = $LinkType->getValue();

        return $this;
    }

    /**
     * @return AttributeLinkType
     */
    public function getLinkType()
    {
        return $this->LinkType;
    }

    /**
     * @param float $NumericValue
     * @return $this
     */
    public function setNumericValue($NumericValue)
    {
        $this->NumericValue = $NumericValue;
        return $this;
    }

    /**
     * @return float
     */
    public function getNumericValue()
    {
        return $this->NumericValue;
    }

    /**
     * @param int $Sequence
     * @return $this
     */
    public function setSequence($Sequence)
    {
        $this->Sequence = $Sequence;
        return $this;
    }

    /**
     * @return int
     */
    public function getSequence()
    {
        return $this->Sequence;
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
     * @param AttributeValueType|string $ValueType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setValueType($ValueType)
    {
        if ( ! $ValueType instanceof AttributeValueType ) {
            if ( AttributeValueType::isValid( $ValueType ) )
                $ValueType = new AttributeValueType( $ValueType );
            elseif ( AttributeValueType::isValidKey( $ValueType ) )
                $ValueType = new AttributeValueType( constant( "AttributeValueType::$ValueType" ) );
            elseif ( ! $ValueType instanceof AttributeValueType )
                throw new InvalidEnumException();
        }
        $this->ValueType = $ValueType->getValue();

        return $this;
    }

    /**
     * @return AttributeValueType
     */
    public function getValueType()
    {
        return $this->ValueType;
    }
}

