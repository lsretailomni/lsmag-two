<?php

namespace Ls\Replication\Api\Data;

interface BarcodeInterface
{

    /**
     * @return boolean
     */
    public function setBlocked($Blocked);
    public function getBlocked();
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
    public function setItemId($ItemId);
    public function getItemId();
    /**
     * @return string
     */
    public function setUom($Uom);
    public function getUom();
    /**
     * @return string
     */
    public function setVariantId($VariantId);
    public function getVariantId();

}

