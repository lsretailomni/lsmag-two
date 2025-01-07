<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplPriceInterface;

class ReplPrice extends AbstractModel implements ReplPriceInterface, IdentityInterface
{
    public const CACHE_TAG = 'ls_replication_repl_price';

    protected $_cacheTag = 'ls_replication_repl_price';

    protected $_eventPrefix = 'ls_replication_repl_price';

    /**
     * @property string $CurrencyCode
     */
    protected $CurrencyCode = null;

    /**
     * @property string $CustomerDiscountGroup
     */
    protected $CustomerDiscountGroup = null;

    /**
     * @property string $EndingDate
     */
    protected $EndingDate = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property string $ItemId
     */
    protected $ItemId = null;

    /**
     * @property int $LineNumber
     */
    protected $LineNumber = null;

    /**
     * @property string $LoyaltySchemeCode
     */
    protected $LoyaltySchemeCode = null;

    /**
     * @property float $MinimumQuantity
     */
    protected $MinimumQuantity = null;

    /**
     * @property string $ModifyDate
     */
    protected $ModifyDate = null;

    /**
     * @property boolean $PriceInclVat
     */
    protected $PriceInclVat = null;

    /**
     * @property string $PriceListCode
     */
    protected $PriceListCode = null;

    /**
     * @property int $Priority
     */
    protected $Priority = null;

    /**
     * @property float $QtyPerUnitOfMeasure
     */
    protected $QtyPerUnitOfMeasure = null;

    /**
     * @property string $SaleCode
     */
    protected $SaleCode = null;

    /**
     * @property PriceType $SaleType
     */
    protected $SaleType = null;

    /**
     * @property string $StartingDate
     */
    protected $StartingDate = null;

    /**
     * @property PriceStatus $Status
     */
    protected $Status = null;

    /**
     * @property string $StoreId
     */
    protected $StoreId = null;

    /**
     * @property string $UnitOfMeasure
     */
    protected $UnitOfMeasure = null;

    /**
     * @property float $UnitPrice
     */
    protected $UnitPrice = null;

    /**
     * @property float $UnitPriceInclVat
     */
    protected $UnitPriceInclVat = null;

    /**
     * @property string $VATPostGroup
     */
    protected $VATPostGroup = null;

    /**
     * @property string $VariantId
     */
    protected $VariantId = null;

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
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplPrice' );
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
     * @param string $CustomerDiscountGroup
     * @return $this
     */
    public function setCustomerDiscountGroup($CustomerDiscountGroup)
    {
        $this->setData( 'CustomerDiscountGroup', $CustomerDiscountGroup );
        $this->CustomerDiscountGroup = $CustomerDiscountGroup;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerDiscountGroup()
    {
        return $this->getData( 'CustomerDiscountGroup' );
    }

    /**
     * @param string $EndingDate
     * @return $this
     */
    public function setEndingDate($EndingDate)
    {
        $this->setData( 'EndingDate', $EndingDate );
        $this->EndingDate = $EndingDate;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getEndingDate()
    {
        return $this->getData( 'EndingDate' );
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
     * @param string $ItemId
     * @return $this
     */
    public function setItemId($ItemId)
    {
        $this->setData( 'ItemId', $ItemId );
        $this->ItemId = $ItemId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->getData( 'ItemId' );
    }

    /**
     * @param int $LineNumber
     * @return $this
     */
    public function setLineNumber($LineNumber)
    {
        $this->setData( 'LineNumber', $LineNumber );
        $this->LineNumber = $LineNumber;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->getData( 'LineNumber' );
    }

    /**
     * @param string $LoyaltySchemeCode
     * @return $this
     */
    public function setLoyaltySchemeCode($LoyaltySchemeCode)
    {
        $this->setData( 'LoyaltySchemeCode', $LoyaltySchemeCode );
        $this->LoyaltySchemeCode = $LoyaltySchemeCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getLoyaltySchemeCode()
    {
        return $this->getData( 'LoyaltySchemeCode' );
    }

    /**
     * @param float $MinimumQuantity
     * @return $this
     */
    public function setMinimumQuantity($MinimumQuantity)
    {
        $this->setData( 'MinimumQuantity', $MinimumQuantity );
        $this->MinimumQuantity = $MinimumQuantity;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getMinimumQuantity()
    {
        return $this->getData( 'MinimumQuantity' );
    }

    /**
     * @param string $ModifyDate
     * @return $this
     */
    public function setModifyDate($ModifyDate)
    {
        $this->setData( 'ModifyDate', $ModifyDate );
        $this->ModifyDate = $ModifyDate;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getModifyDate()
    {
        return $this->getData( 'ModifyDate' );
    }

    /**
     * @param boolean $PriceInclVat
     * @return $this
     */
    public function setPriceInclVat($PriceInclVat)
    {
        $this->setData( 'PriceInclVat', $PriceInclVat );
        $this->PriceInclVat = $PriceInclVat;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getPriceInclVat()
    {
        return $this->getData( 'PriceInclVat' );
    }

    /**
     * @param string $PriceListCode
     * @return $this
     */
    public function setPriceListCode($PriceListCode)
    {
        $this->setData( 'PriceListCode', $PriceListCode );
        $this->PriceListCode = $PriceListCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getPriceListCode()
    {
        return $this->getData( 'PriceListCode' );
    }

    /**
     * @param int $Priority
     * @return $this
     */
    public function setPriority($Priority)
    {
        $this->setData( 'Priority', $Priority );
        $this->Priority = $Priority;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->getData( 'Priority' );
    }

    /**
     * @param float $QtyPerUnitOfMeasure
     * @return $this
     */
    public function setQtyPerUnitOfMeasure($QtyPerUnitOfMeasure)
    {
        $this->setData( 'QtyPerUnitOfMeasure', $QtyPerUnitOfMeasure );
        $this->QtyPerUnitOfMeasure = $QtyPerUnitOfMeasure;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getQtyPerUnitOfMeasure()
    {
        return $this->getData( 'QtyPerUnitOfMeasure' );
    }

    /**
     * @param string $SaleCode
     * @return $this
     */
    public function setSaleCode($SaleCode)
    {
        $this->setData( 'SaleCode', $SaleCode );
        $this->SaleCode = $SaleCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getSaleCode()
    {
        return $this->getData( 'SaleCode' );
    }

    /**
     * @param PriceType $SaleType
     * @return $this
     */
    public function setSaleType($SaleType)
    {
        $this->setData( 'SaleType', $SaleType );
        $this->SaleType = $SaleType;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return PriceType
     */
    public function getSaleType()
    {
        return $this->getData( 'SaleType' );
    }

    /**
     * @param string $StartingDate
     * @return $this
     */
    public function setStartingDate($StartingDate)
    {
        $this->setData( 'StartingDate', $StartingDate );
        $this->StartingDate = $StartingDate;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getStartingDate()
    {
        return $this->getData( 'StartingDate' );
    }

    /**
     * @param PriceStatus $Status
     * @return $this
     */
    public function setStatus($Status)
    {
        $this->setData( 'Status', $Status );
        $this->Status = $Status;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return PriceStatus
     */
    public function getStatus()
    {
        return $this->getData( 'Status' );
    }

    /**
     * @param string $StoreId
     * @return $this
     */
    public function setStoreId($StoreId)
    {
        $this->setData( 'StoreId', $StoreId );
        $this->StoreId = $StoreId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->getData( 'StoreId' );
    }

    /**
     * @param string $UnitOfMeasure
     * @return $this
     */
    public function setUnitOfMeasure($UnitOfMeasure)
    {
        $this->setData( 'UnitOfMeasure', $UnitOfMeasure );
        $this->UnitOfMeasure = $UnitOfMeasure;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getUnitOfMeasure()
    {
        return $this->getData( 'UnitOfMeasure' );
    }

    /**
     * @param float $UnitPrice
     * @return $this
     */
    public function setUnitPrice($UnitPrice)
    {
        $this->setData( 'UnitPrice', $UnitPrice );
        $this->UnitPrice = $UnitPrice;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getUnitPrice()
    {
        return $this->getData( 'UnitPrice' );
    }

    /**
     * @param float $UnitPriceInclVat
     * @return $this
     */
    public function setUnitPriceInclVat($UnitPriceInclVat)
    {
        $this->setData( 'UnitPriceInclVat', $UnitPriceInclVat );
        $this->UnitPriceInclVat = $UnitPriceInclVat;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getUnitPriceInclVat()
    {
        return $this->getData( 'UnitPriceInclVat' );
    }

    /**
     * @param string $VATPostGroup
     * @return $this
     */
    public function setVATPostGroup($VATPostGroup)
    {
        $this->setData( 'VATPostGroup', $VATPostGroup );
        $this->VATPostGroup = $VATPostGroup;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getVATPostGroup()
    {
        return $this->getData( 'VATPostGroup' );
    }

    /**
     * @param string $VariantId
     * @return $this
     */
    public function setVariantId($VariantId)
    {
        $this->setData( 'VariantId', $VariantId );
        $this->VariantId = $VariantId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getVariantId()
    {
        return $this->getData( 'VariantId' );
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

