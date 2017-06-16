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

    /**
     * @return $this
     */
    public function setAmountRoundingMethod($AmountRoundingMethod)
    {
        $this->setData( 'AmountRoundingMethod', $AmountRoundingMethod );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getAmountRoundingMethod()
    {
        return $this->AmountRoundingMethod;
    }

    /**
     * @return $this
     */
    public function setCulture($Culture)
    {
        $this->setData( 'Culture', $Culture );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCulture()
    {
        return $this->Culture;
    }

    /**
     * @return $this
     */
    public function setDecimalPlaces($DecimalPlaces)
    {
        $this->setData( 'DecimalPlaces', $DecimalPlaces );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDecimalPlaces()
    {
        return $this->DecimalPlaces;
    }

    /**
     * @return $this
     */
    public function setDecimalSeparator($DecimalSeparator)
    {
        $this->setData( 'DecimalSeparator', $DecimalSeparator );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDecimalSeparator()
    {
        return $this->DecimalSeparator;
    }

    /**
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->setData( 'Description', $Description );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * @return $this
     */
    public function setId($Id)
    {
        $this->setData( 'Id', $Id );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getId()
    {
        return $this->Id;
    }

    /**
     * @return $this
     */
    public function setPostfix($Postfix)
    {
        $this->setData( 'Postfix', $Postfix );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getPostfix()
    {
        return $this->Postfix;
    }

    /**
     * @return $this
     */
    public function setPrefix($Prefix)
    {
        $this->setData( 'Prefix', $Prefix );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getPrefix()
    {
        return $this->Prefix;
    }

    /**
     * @return $this
     */
    public function setRoundOfAmount($RoundOfAmount)
    {
        $this->setData( 'RoundOfAmount', $RoundOfAmount );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getRoundOfAmount()
    {
        return $this->RoundOfAmount;
    }

    /**
     * @return $this
     */
    public function setRoundOffSales($RoundOffSales)
    {
        $this->setData( 'RoundOffSales', $RoundOffSales );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getRoundOffSales()
    {
        return $this->RoundOffSales;
    }

    /**
     * @return $this
     */
    public function setSaleRoundingMethod($SaleRoundingMethod)
    {
        $this->setData( 'SaleRoundingMethod', $SaleRoundingMethod );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getSaleRoundingMethod()
    {
        return $this->SaleRoundingMethod;
    }

    /**
     * @return $this
     */
    public function setSymbol($Symbol)
    {
        $this->setData( 'Symbol', $Symbol );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getSymbol()
    {
        return $this->Symbol;
    }

    /**
     * @return $this
     */
    public function setThousandSeparator($ThousandSeparator)
    {
        $this->setData( 'ThousandSeparator', $ThousandSeparator );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getThousandSeparator()
    {
        return $this->ThousandSeparator;
    }

    /**
     * @return $this
     */
    public function setCurrencyCode($CurrencyCode)
    {
        $this->setData( 'CurrencyCode', $CurrencyCode );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCurrencyCode()
    {
        return $this->CurrencyCode;
    }

    /**
     * @return $this
     */
    public function setCurrencyPrefix($CurrencyPrefix)
    {
        $this->setData( 'CurrencyPrefix', $CurrencyPrefix );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCurrencyPrefix()
    {
        return $this->CurrencyPrefix;
    }

    /**
     * @return $this
     */
    public function setCurrencySuffix($CurrencySuffix)
    {
        $this->setData( 'CurrencySuffix', $CurrencySuffix );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCurrencySuffix()
    {
        return $this->CurrencySuffix;
    }

    /**
     * @return $this
     */
    public function setDel($Del)
    {
        $this->setData( 'Del', $Del );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    /**
     * @return $this
     */
    public function setRoundOfSales($RoundOfSales)
    {
        $this->setData( 'RoundOfSales', $RoundOfSales );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getRoundOfSales()
    {
        return $this->RoundOfSales;
    }

    /**
     * @return $this
     */
    public function setRoundOfTypeAmount($RoundOfTypeAmount)
    {
        $this->setData( 'RoundOfTypeAmount', $RoundOfTypeAmount );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getRoundOfTypeAmount()
    {
        return $this->RoundOfTypeAmount;
    }

    /**
     * @return $this
     */
    public function setRoundOfTypeSales($RoundOfTypeSales)
    {
        $this->setData( 'RoundOfTypeSales', $RoundOfTypeSales );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getRoundOfTypeSales()
    {
        return $this->RoundOfTypeSales;
    }


}

