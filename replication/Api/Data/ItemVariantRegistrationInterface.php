<?php

namespace Ls\Replication\Api\Data;

interface ItemVariantRegistrationInterface
{

    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return string
     */
    public function setFrameworkCode($FrameworkCode);
    public function getFrameworkCode();
    /**
     * @return string
     */
    public function setItemId($ItemId);
    public function getItemId();
    /**
     * @return string
     */
    public function setVarDim1($VarDim1);
    public function getVarDim1();
    /**
     * @return string
     */
    public function setVarDim2($VarDim2);
    public function getVarDim2();
    /**
     * @return string
     */
    public function setVarDim3($VarDim3);
    public function getVarDim3();
    /**
     * @return string
     */
    public function setVarDim4($VarDim4);
    public function getVarDim4();
    /**
     * @return string
     */
    public function setVarDim5($VarDim5);
    public function getVarDim5();
    /**
     * @return string
     */
    public function setVarDim6($VarDim6);
    public function getVarDim6();
    /**
     * @return string
     */
    public function setVariantId($VariantId);
    public function getVariantId();

}

