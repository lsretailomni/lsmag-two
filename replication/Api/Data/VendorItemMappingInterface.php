<?php

namespace Ls\Replication\Api\Data;

interface VendorItemMappingInterface
{

    /**
     * @return int
     */
    public function setDO($DO);
    public function getDO();
    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return boolean
     */
    public function setDeleted($Deleted);
    public function getDeleted();
    /**
     * @return boolean
     */
    public function setFP($FP);
    public function getFP();
    /**
     * @return string
     */
    public function setMId($MId);
    public function getMId();
    /**
     * @return string
     */
    public function setNId($NId);
    public function getNId();

}

