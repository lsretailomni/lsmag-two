<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\CurrencyInterface;

class Currency extends AbstractModel implements CurrencyInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_currency';

    protected $_cacheTag = 'lsr_replication_currency';

    protected $_eventPrefix = 'lsr_replication_currency';

    protected $AmountRoundingMethod = null;

    protected $Culture = null;

    protected $DecimalPlaces = null;

    protected $DecimalSeparator = null;

    protected $Description = null;

    protected $Id = null;

    protected $Postfix = null;

    protected $Prefix = null;

    protected $RoundOfAmount = null;

    protected $RoundOffSales = null;

    protected $SaleRoundingMethod = null;

    protected $Symbol = null;

    protected $ThousandSeparator = null;

    protected $CurrencyCode = null;

    protected $CurrencyPrefix = null;

    protected $CurrencySuffix = null;

    protected $Del = null;

    protected $RoundOfSales = null;

    protected $RoundOfTypeAmount = null;

    protected $RoundOfTypeSales = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Currency' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    public function setAmountRoundingMethod($AmountRoundingMethod)
    {
        $this->AmountRoundingMethod = $AmountRoundingMethod;
        return $this;
    }

    public function getAmountRoundingMethod()
    {
        return $this->AmountRoundingMethod;
    }

    public function setCulture($Culture)
    {
        $this->Culture = $Culture;
        return $this;
    }

    public function getCulture()
    {
        return $this->Culture;
    }

    public function setDecimalPlaces($DecimalPlaces)
    {
        $this->DecimalPlaces = $DecimalPlaces;
        return $this;
    }

    public function getDecimalPlaces()
    {
        return $this->DecimalPlaces;
    }

    public function setDecimalSeparator($DecimalSeparator)
    {
        $this->DecimalSeparator = $DecimalSeparator;
        return $this;
    }

    public function getDecimalSeparator()
    {
        return $this->DecimalSeparator;
    }

    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    public function getDescription()
    {
        return $this->Description;
    }

    public function setId($Id)
    {
        $this->Id = $Id;
        return $this;
    }

    public function getId()
    {
        return $this->Id;
    }

    public function setPostfix($Postfix)
    {
        $this->Postfix = $Postfix;
        return $this;
    }

    public function getPostfix()
    {
        return $this->Postfix;
    }

    public function setPrefix($Prefix)
    {
        $this->Prefix = $Prefix;
        return $this;
    }

    public function getPrefix()
    {
        return $this->Prefix;
    }

    public function setRoundOfAmount($RoundOfAmount)
    {
        $this->RoundOfAmount = $RoundOfAmount;
        return $this;
    }

    public function getRoundOfAmount()
    {
        return $this->RoundOfAmount;
    }

    public function setRoundOffSales($RoundOffSales)
    {
        $this->RoundOffSales = $RoundOffSales;
        return $this;
    }

    public function getRoundOffSales()
    {
        return $this->RoundOffSales;
    }

    public function setSaleRoundingMethod($SaleRoundingMethod)
    {
        $this->SaleRoundingMethod = $SaleRoundingMethod;
        return $this;
    }

    public function getSaleRoundingMethod()
    {
        return $this->SaleRoundingMethod;
    }

    public function setSymbol($Symbol)
    {
        $this->Symbol = $Symbol;
        return $this;
    }

    public function getSymbol()
    {
        return $this->Symbol;
    }

    public function setThousandSeparator($ThousandSeparator)
    {
        $this->ThousandSeparator = $ThousandSeparator;
        return $this;
    }

    public function getThousandSeparator()
    {
        return $this->ThousandSeparator;
    }

    public function setCurrencyCode($CurrencyCode)
    {
        $this->CurrencyCode = $CurrencyCode;
        return $this;
    }

    public function getCurrencyCode()
    {
        return $this->CurrencyCode;
    }

    public function setCurrencyPrefix($CurrencyPrefix)
    {
        $this->CurrencyPrefix = $CurrencyPrefix;
        return $this;
    }

    public function getCurrencyPrefix()
    {
        return $this->CurrencyPrefix;
    }

    public function setCurrencySuffix($CurrencySuffix)
    {
        $this->CurrencySuffix = $CurrencySuffix;
        return $this;
    }

    public function getCurrencySuffix()
    {
        return $this->CurrencySuffix;
    }

    public function setDel($Del)
    {
        $this->Del = $Del;
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    public function setRoundOfSales($RoundOfSales)
    {
        $this->RoundOfSales = $RoundOfSales;
        return $this;
    }

    public function getRoundOfSales()
    {
        return $this->RoundOfSales;
    }

    public function setRoundOfTypeAmount($RoundOfTypeAmount)
    {
        $this->RoundOfTypeAmount = $RoundOfTypeAmount;
        return $this;
    }

    public function getRoundOfTypeAmount()
    {
        return $this->RoundOfTypeAmount;
    }

    public function setRoundOfTypeSales($RoundOfTypeSales)
    {
        $this->RoundOfTypeSales = $RoundOfTypeSales;
        return $this;
    }

    public function getRoundOfTypeSales()
    {
        return $this->RoundOfTypeSales;
    }


}

