<?php

namespace Ls\Replication\Api\Data;

interface AttributeValueInterface
{

    /**
     * @return string
     */
    public function setCode($Code);
    public function getCode();
    /**
     * @return boolean
     */
    public function setIsDeleted($IsDeleted);
    public function getIsDeleted();
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
     * @return int
     */
    public function setLinkType($LinkType);
    public function getLinkType();
    /**
     * @return float
     */
    public function setNumbericValue($NumbericValue);
    public function getNumbericValue();
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

}

