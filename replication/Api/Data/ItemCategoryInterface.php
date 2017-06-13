<?php

namespace Ls\Replication\Api\Data;

interface ItemCategoryInterface
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

}

