<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplCurrencyInterface;

class ReplCurrency extends AbstractModel implements ReplCurrencyInterface, IdentityInterface
{
    public const CACHE_TAG = 'ls_replication_repl_currency';

    protected $_cacheTag = 'ls_replication_repl_currency';

    protected $_eventPrefix = 'ls_replication_repl_currency';

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
     * @property boolean $processed
     */
    protected $processed = null;

    /**
     * @property boolean $is_updated
     */
    protected $is_updated = null;

    /**
     * @property boolean $is_failed
     */
    protected $is_failed = null;

    /**
     * @property string $created_at
     */
    protected $created_at = null;

    /**
     * @property string $updated_at
     */
    protected $updated_at = null;

    /**
     * @property string $identity_value
     */
    protected $identity_value = null;

    /**
     * @property string $checksum
     */
    protected $checksum = null;

    /**
     * @property string $processed_at
     */
    protected $processed_at = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplCurrency' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @param string $CurrencyCode
     * @return $this
     */
    public function setCurrencyCode($CurrencyCode)
    {
        $this->setData( 'CurrencyCode', $CurrencyCode );
        $this->CurrencyCode = $CurrencyCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->getData( 'CurrencyCode' );
    }

    /**
     * @param string $CurrencyPrefix
     * @return $this
     */
    public function setCurrencyPrefix($CurrencyPrefix)
    {
        $this->setData( 'CurrencyPrefix', $CurrencyPrefix );
        $this->CurrencyPrefix = $CurrencyPrefix;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyPrefix()
    {
        return $this->getData( 'CurrencyPrefix' );
    }

    /**
     * @param string $CurrencySuffix
     * @return $this
     */
    public function setCurrencySuffix($CurrencySuffix)
    {
        $this->setData( 'CurrencySuffix', $CurrencySuffix );
        $this->CurrencySuffix = $CurrencySuffix;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencySuffix()
    {
        return $this->getData( 'CurrencySuffix' );
    }

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->setData( 'Description', $Description );
        $this->Description = $Description;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getData( 'Description' );
    }

    /**
     * @param boolean $IsDeleted
     * @return $this
     */
    public function setIsDeleted($IsDeleted)
    {
        $this->setData( 'IsDeleted', $IsDeleted );
        $this->IsDeleted = $IsDeleted;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->getData( 'IsDeleted' );
    }

    /**
     * @param float $RoundOfAmount
     * @return $this
     */
    public function setRoundOfAmount($RoundOfAmount)
    {
        $this->setData( 'RoundOfAmount', $RoundOfAmount );
        $this->RoundOfAmount = $RoundOfAmount;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getRoundOfAmount()
    {
        return $this->getData( 'RoundOfAmount' );
    }

    /**
     * @param float $RoundOfSales
     * @return $this
     */
    public function setRoundOfSales($RoundOfSales)
    {
        $this->setData( 'RoundOfSales', $RoundOfSales );
        $this->RoundOfSales = $RoundOfSales;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getRoundOfSales()
    {
        return $this->getData( 'RoundOfSales' );
    }

    /**
     * @param int $RoundOfTypeAmount
     * @return $this
     */
    public function setRoundOfTypeAmount($RoundOfTypeAmount)
    {
        $this->setData( 'RoundOfTypeAmount', $RoundOfTypeAmount );
        $this->RoundOfTypeAmount = $RoundOfTypeAmount;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getRoundOfTypeAmount()
    {
        return $this->getData( 'RoundOfTypeAmount' );
    }

    /**
     * @param int $RoundOfTypeSales
     * @return $this
     */
    public function setRoundOfTypeSales($RoundOfTypeSales)
    {
        $this->setData( 'RoundOfTypeSales', $RoundOfTypeSales );
        $this->RoundOfTypeSales = $RoundOfTypeSales;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getRoundOfTypeSales()
    {
        return $this->getData( 'RoundOfTypeSales' );
    }

    /**
     * @param string $Symbol
     * @return $this
     */
    public function setSymbol($Symbol)
    {
        $this->setData( 'Symbol', $Symbol );
        $this->Symbol = $Symbol;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getSymbol()
    {
        return $this->getData( 'Symbol' );
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->setData( 'scope', $scope );
        $this->scope = $scope;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->getData( 'scope' );
    }

    /**
     * @param int $scope_id
     * @return $this
     */
    public function setScopeId($scope_id)
    {
        $this->setData( 'scope_id', $scope_id );
        $this->scope_id = $scope_id;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        return $this->getData( 'scope_id' );
    }

    /**
     * @param boolean $processed
     * @return $this
     */
    public function setProcessed($processed)
    {
        $this->setData( 'processed', $processed );
        $this->processed = $processed;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getProcessed()
    {
        return $this->getData( 'processed' );
    }

    /**
     * @param boolean $is_updated
     * @return $this
     */
    public function setIsUpdated($is_updated)
    {
        $this->setData( 'is_updated', $is_updated );
        $this->is_updated = $is_updated;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsUpdated()
    {
        return $this->getData( 'is_updated' );
    }

    /**
     * @param boolean $is_failed
     * @return $this
     */
    public function setIsFailed($is_failed)
    {
        $this->setData( 'is_failed', $is_failed );
        $this->is_failed = $is_failed;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsFailed()
    {
        return $this->getData( 'is_failed' );
    }

    /**
     * @param string $created_at
     * @return $this
     */
    public function setCreatedAt($created_at)
    {
        $this->setData( 'created_at', $created_at );
        $this->created_at = $created_at;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData( 'created_at' );
    }

    /**
     * @param string $updated_at
     * @return $this
     */
    public function setUpdatedAt($updated_at)
    {
        $this->setData( 'updated_at', $updated_at );
        $this->updated_at = $updated_at;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData( 'updated_at' );
    }

    /**
     * @param string $identity_value
     * @return $this
     */
    public function setIdentityValue($identity_value)
    {
        $this->setData( 'identity_value', $identity_value );
        $this->identity_value = $identity_value;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentityValue()
    {
        return $this->getData( 'identity_value' );
    }

    /**
     * @param string $checksum
     * @return $this
     */
    public function setChecksum($checksum)
    {
        $this->setData( 'checksum', $checksum );
        $this->checksum = $checksum;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getChecksum()
    {
        return $this->getData( 'checksum' );
    }

    /**
     * @param string $processed_at
     * @return $this
     */
    public function setProcessedAt($processed_at)
    {
        $this->setData( 'processed_at', $processed_at );
        $this->processed_at = $processed_at;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getProcessedAt()
    {
        return $this->getData( 'processed_at' );
    }
}

