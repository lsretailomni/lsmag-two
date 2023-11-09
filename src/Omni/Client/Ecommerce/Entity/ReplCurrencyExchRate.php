<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ReplCurrencyExchRate
{
    /**
     * @property string $CurrencyCode
     */
    protected $CurrencyCode = null;

    /**
     * @property float $CurrencyFactor
     */
    protected $CurrencyFactor = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property string $RelationalCurrencyCode
     */
    protected $RelationalCurrencyCode = null;

    /**
     * @property string $StartingDate
     */
    protected $StartingDate = null;

    /**
     * @property string $scope
     */
    protected $scope = null;

    /**
     * @property int $scope_id
     */
    protected $scope_id = null;

    /**
     * @param string $CurrencyCode
     * @return $this
     */
    public function setCurrencyCode($CurrencyCode)
    {
        $this->CurrencyCode = $CurrencyCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->CurrencyCode;
    }

    /**
     * @param float $CurrencyFactor
     * @return $this
     */
    public function setCurrencyFactor($CurrencyFactor)
    {
        $this->CurrencyFactor = $CurrencyFactor;
        return $this;
    }

    /**
     * @return float
     */
    public function getCurrencyFactor()
    {
        return $this->CurrencyFactor;
    }

    /**
     * @param boolean $IsDeleted
     * @return $this
     */
    public function setIsDeleted($IsDeleted)
    {
        $this->IsDeleted = $IsDeleted;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->IsDeleted;
    }

    /**
     * @param string $RelationalCurrencyCode
     * @return $this
     */
    public function setRelationalCurrencyCode($RelationalCurrencyCode)
    {
        $this->RelationalCurrencyCode = $RelationalCurrencyCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getRelationalCurrencyCode()
    {
        return $this->RelationalCurrencyCode;
    }

    /**
     * @param string $StartingDate
     * @return $this
     */
    public function setStartingDate($StartingDate)
    {
        $this->StartingDate = $StartingDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getStartingDate()
    {
        return $this->StartingDate;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param int $scope_id
     * @return $this
     */
    public function setScopeId($scope_id)
    {
        $this->scope_id = $scope_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        return $this->scope_id;
    }
}

