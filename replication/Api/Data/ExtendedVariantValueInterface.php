<?php

namespace Ls\Replication\Api\Data;

interface ExtendedVariantValueInterface
{

    /**
     * @return string
     */
    public function setCode($Code);
    public function getCode();
    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return string
     */
    public function setDimensions($Dimensions);
    public function getDimensions();
    /**
     * @return string
     */
    public function setFrameworkCode($FrameworkCode);
    public function getFrameworkCode();
    /**
     * @return string
     */
    public function setItemId($ItemId);
    public function getItemId();
    /**
     * @return int
     */
    public function setOrder($Order);
    public function getOrder();
    /**
     * @return string
     */
    public function setTimestamp($Timestamp);
    public function getTimestamp();
    /**
     * @return string
     */
    public function setValue($Value);
    public function getValue();

}

