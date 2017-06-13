<?php

namespace Ls\Replication\Api\Data;

interface CurrencyRateInterface
{

    /**
     * @return string
     */
    public function setCC($CC);
    public function getCC();
    /**
     * @return float
     */
    public function setCF($CF);
    public function getCF();
    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return string
     */
    public function setSD($SD);
    public function getSD();

}

