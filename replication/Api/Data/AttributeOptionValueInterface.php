<?php

namespace Ls\Replication\Api\Data;

interface AttributeOptionValueInterface
{

    /**
     * @return string
     */
    public function setCode($Code);
    public function getCode();
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
     * @return boolean
     */
    public function setIsDeleted($IsDeleted);
    public function getIsDeleted();

}

