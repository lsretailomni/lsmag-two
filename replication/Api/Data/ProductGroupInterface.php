<?php

namespace Ls\Replication\Api\Data;

interface ProductGroupInterface
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
    public function setItemCategoryID($ItemCategoryID);
    public function getItemCategoryID();

}

