<?php

namespace Ls\Replication\Api\Data;

interface DataTranslationInterface
{

    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return string
     */
    public function setKey($Key);
    public function getKey();
    /**
     * @return string
     */
    public function setLC($LC);
    public function getLC();
    /**
     * @return string
     */
    public function setTId($TId);
    public function getTId();
    /**
     * @return string
     */
    public function setTx($Tx);
    public function getTx();

}

