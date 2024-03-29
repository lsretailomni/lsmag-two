<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class AttributeResponse
{
    /**
     * @property string $AttributeCode
     */
    protected $AttributeCode = null;

    /**
     * @property string $AttributeValue
     */
    protected $AttributeValue = null;

    /**
     * @property string $AttributeValueType
     */
    protected $AttributeValueType = null;

    /**
     * @property string $LinkField
     */
    protected $LinkField = null;

    /**
     * @property int $Sequence
     */
    protected $Sequence = null;

    /**
     * @param string $AttributeCode
     * @return $this
     */
    public function setAttributeCode($AttributeCode)
    {
        $this->AttributeCode = $AttributeCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->AttributeCode;
    }

    /**
     * @param string $AttributeValue
     * @return $this
     */
    public function setAttributeValue($AttributeValue)
    {
        $this->AttributeValue = $AttributeValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getAttributeValue()
    {
        return $this->AttributeValue;
    }

    /**
     * @param string $AttributeValueType
     * @return $this
     */
    public function setAttributeValueType($AttributeValueType)
    {
        $this->AttributeValueType = $AttributeValueType;
        return $this;
    }

    /**
     * @return string
     */
    public function getAttributeValueType()
    {
        return $this->AttributeValueType;
    }

    /**
     * @param string $LinkField
     * @return $this
     */
    public function setLinkField($LinkField)
    {
        $this->LinkField = $LinkField;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkField()
    {
        return $this->LinkField;
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
}

