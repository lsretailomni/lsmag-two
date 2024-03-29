<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ReplCurrency
{
    /**
     * @property string $CurrencyCode
     */
    protected $CurrencyCode = null;

    /**
     * @property string $CurrencyPrefix
     */
    protected $CurrencyPrefix = null;

    /**
     * @property string $CurrencySuffix
     */
    protected $CurrencySuffix = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property float $RoundOfAmount
     */
    protected $RoundOfAmount = null;

    /**
     * @property float $RoundOfSales
     */
    protected $RoundOfSales = null;

    /**
     * @property int $RoundOfTypeAmount
     */
    protected $RoundOfTypeAmount = null;

    /**
     * @property int $RoundOfTypeSales
     */
    protected $RoundOfTypeSales = null;

    /**
     * @property string $Symbol
     */
    protected $Symbol = null;

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
     * @param string $CurrencyPrefix
     * @return $this
     */
    public function setCurrencyPrefix($CurrencyPrefix)
    {
        $this->CurrencyPrefix = $CurrencyPrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyPrefix()
    {
        return $this->CurrencyPrefix;
    }

    /**
     * @param string $CurrencySuffix
     * @return $this
     */
    public function setCurrencySuffix($CurrencySuffix)
    {
        $this->CurrencySuffix = $CurrencySuffix;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencySuffix()
    {
        return $this->CurrencySuffix;
    }

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
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
     * @param float $RoundOfAmount
     * @return $this
     */
    public function setRoundOfAmount($RoundOfAmount)
    {
        $this->RoundOfAmount = $RoundOfAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getRoundOfAmount()
    {
        return $this->RoundOfAmount;
    }

    /**
     * @param float $RoundOfSales
     * @return $this
     */
    public function setRoundOfSales($RoundOfSales)
    {
        $this->RoundOfSales = $RoundOfSales;
        return $this;
    }

    /**
     * @return float
     */
    public function getRoundOfSales()
    {
        return $this->RoundOfSales;
    }

    /**
     * @param int $RoundOfTypeAmount
     * @return $this
     */
    public function setRoundOfTypeAmount($RoundOfTypeAmount)
    {
        $this->RoundOfTypeAmount = $RoundOfTypeAmount;
        return $this;
    }

    /**
     * @return int
     */
    public function getRoundOfTypeAmount()
    {
        return $this->RoundOfTypeAmount;
    }

    /**
     * @param int $RoundOfTypeSales
     * @return $this
     */
    public function setRoundOfTypeSales($RoundOfTypeSales)
    {
        $this->RoundOfTypeSales = $RoundOfTypeSales;
        return $this;
    }

    /**
     * @return int
     */
    public function getRoundOfTypeSales()
    {
        return $this->RoundOfTypeSales;
    }

    /**
     * @param string $Symbol
     * @return $this
     */
    public function setSymbol($Symbol)
    {
        $this->Symbol = $Symbol;
        return $this;
    }

    /**
     * @return string
     */
    public function getSymbol()
    {
        return $this->Symbol;
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

