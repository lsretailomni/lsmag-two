<?php

namespace Ls\Replication\Api\Data;

interface AttributeInterface
{

    /**
     * @return string
     */
    public function setCode($Code);
    public function getCode();
    /**
     * @return string
     */
    public function setDefaultValue($DefaultValue);
    public function getDefaultValue();
    /**
     * @return string
     */
    public function setDescription($Description);
    public function getDescription();
    /**
     * @return string
     */
    public function setLinkField1($LinkField1);
    public function getLinkField1();
    /**
     * @return string
     */
    public function setLinkField2($LinkField2);
    public function getLinkField2();
    /**
     * @return string
     */
    public function setLinkField3($LinkField3);
    public function getLinkField3();
    /**
     * @return AttributeLinkType
     */
    public function setLinkType($LinkType);
    public function getLinkType();
    /**
     * @return float
     */
    public function setNumbericValue($NumbericValue);
    public function getNumbericValue();
    /**
     * @return ArrayOfAttributeOptionValue
     */
    public function setOptionValues($OptionValues);
    public function getOptionValues();
    /**
     * @return int
     */
    public function setSequence($Sequence);
    public function getSequence();
    /**
     * @return string
     */
    public function setValue($Value);
    public function getValue();
    /**
     * @return int
     */
    public function setValueType($ValueType);
    public function getValueType();
    /**
     * @return boolean
     */
    public function setIsDeleted($IsDeleted);
    public function getIsDeleted();

}

