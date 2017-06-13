<?php

namespace Ls\Replication\Api\Data;

interface VendorInterface
{

    /**
     * @return boolean
     */
    public function setACTSPS($ACTSPS);
    public function getACTSPS();
    /**
     * @return string
     */
    public function setCOUtc($COUtc);
    public function getCOUtc();
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
     * @return string
     */
    public function setId($Id);
    public function getId();
    /**
     * @return int
     */
    public function setMTId($MTId);
    public function getMTId();
    /**
     * @return string
     */
    public function setName($Name);
    public function getName();
    /**
     * @return int
     */
    public function setPId($PId);
    public function getPId();
    /**
     * @return int
     */
    public function setPS($PS);
    public function getPS();
    /**
     * @return string
     */
    public function setPSO($PSO);
    public function getPSO();
    /**
     * @return boolean
     */
    public function setPub($Pub);
    public function getPub();
    /**
     * @return string
     */
    public function setUOUtc($UOUtc);
    public function getUOUtc();

}

