<?php

namespace Ls\Replication\Api\Data;

interface UnitOfMeasureInterface
{

    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return string
     */
    public function setDescription($Description);
    public function getDescription();
    /**
     * @return string
     */
    public function setId($Id);
    public function getId();
    /**
     * @return string
     */
    public function setShortDescription($ShortDescription);
    public function getShortDescription();
    /**
     * @return int
     */
    public function setUnitDecimals($UnitDecimals);
    public function getUnitDecimals();

}

