<?php

namespace Ls\Replication\Api\Data;

interface ImageInterface
{

    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return string
     */
    public function setId($Id);
    public function getId();
    /**
     * @return string
     */
    public function setImage64($Image64);
    public function getImage64();
    /**
     * @return string
     */
    public function setLocation($Location);
    public function getLocation();
    /**
     * @return LocationType
     */
    public function setLocationType($LocationType);
    public function getLocationType();

}

