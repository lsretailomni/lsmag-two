<?php

namespace Ls\Replication\Api\Data;

interface ItemUOMInterface
{

    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return string
     */
    public function setItemId($ItemId);
    public function getItemId();
    /**
     * @return float
     */
    public function setQtyPrUom($QtyPrUom);
    public function getQtyPrUom();
    /**
     * @return string
     */
    public function setStoreId($StoreId);
    public function getStoreId();
    /**
     * @return string
     */
    public function setUomCode($UomCode);
    public function getUomCode();

}

