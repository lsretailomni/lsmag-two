<?php

namespace Ls\Replication\Api\Data;

interface ImageLinkInterface
{

    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return int
     */
    public function setDisplayOrder($DisplayOrder);
    public function getDisplayOrder();
    /**
     * @return string
     */
    public function setImageId($ImageId);
    public function getImageId();
    /**
     * @return string
     */
    public function setKeyValue($KeyValue);
    public function getKeyValue();
    /**
     * @return string
     */
    public function setTableName($TableName);
    public function getTableName();

}

