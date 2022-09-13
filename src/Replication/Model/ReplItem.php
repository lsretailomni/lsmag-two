<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplItemInterface;

class ReplItem extends AbstractModel implements ReplItemInterface, IdentityInterface
{

    public const CACHE_TAG = 'ls_replication_repl_item';

    protected $_cacheTag = 'ls_replication_repl_item';

    protected $_eventPrefix = 'ls_replication_repl_item';

    /**
     * @property string $BaseUnitOfMeasure
     */
    protected $BaseUnitOfMeasure = null;

    /**
     * @property int $BlockDiscount
     */
    protected $BlockDiscount = null;

    /**
     * @property int $BlockDistribution
     */
    protected $BlockDistribution = null;

    /**
     * @property int $BlockManualPriceChange
     */
    protected $BlockManualPriceChange = null;

    /**
     * @property int $BlockNegativeAdjustment
     */
    protected $BlockNegativeAdjustment = null;

    /**
     * @property int $BlockPositiveAdjustment
     */
    protected $BlockPositiveAdjustment = null;

    /**
     * @property int $BlockPurchaseReturn
     */
    protected $BlockPurchaseReturn = null;

    /**
     * @property int $Blocked
     */
    protected $Blocked = null;

    /**
     * @property int $BlockedOnECom
     */
    protected $BlockedOnECom = null;

    /**
     * @property int $BlockedOnPos
     */
    protected $BlockedOnPos = null;

    /**
     * @property string $CountryOfOrigin
     */
    protected $CountryOfOrigin = null;

    /**
     * @property int $CrossSellingExists
     */
    protected $CrossSellingExists = null;

    /**
     * @property string $DateBlocked
     */
    protected $DateBlocked = null;

    /**
     * @property string $DateToActivateItem
     */
    protected $DateToActivateItem = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $Details
     */
    protected $Details = null;

    /**
     * @property float $GrossWeight
     */
    protected $GrossWeight = null;

    /**
     * @property string $nav_id
     */
    protected $nav_id = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property string $ItemCategoryCode
     */
    protected $ItemCategoryCode = null;

    /**
     * @property string $ItemFamilyCode
     */
    protected $ItemFamilyCode = null;

    /**
     * @property int $KeyingInPrice
     */
    protected $KeyingInPrice = null;

    /**
     * @property int $KeyingInQty
     */
    protected $KeyingInQty = null;

    /**
     * @property int $MustKeyInComment
     */
    protected $MustKeyInComment = null;

    /**
     * @property int $NoDiscountAllowed
     */
    protected $NoDiscountAllowed = null;

    /**
     * @property string $ProductGroupId
     */
    protected $ProductGroupId = null;

    /**
     * @property string $PurchUnitOfMeasure
     */
    protected $PurchUnitOfMeasure = null;

    /**
     * @property string $SalseUnitOfMeasure
     */
    protected $SalseUnitOfMeasure = null;

    /**
     * @property int $ScaleItem
     */
    protected $ScaleItem = null;

    /**
     * @property string $SeasonCode
     */
    protected $SeasonCode = null;

    /**
     * @property string $TaxItemGroupId
     */
    protected $TaxItemGroupId = null;

    /**
     * @property float $UnitPrice
     */
    protected $UnitPrice = null;

    /**
     * @property float $UnitVolume
     */
    protected $UnitVolume = null;

    /**
     * @property float $UnitsPerParcel
     */
    protected $UnitsPerParcel = null;

    /**
     * @property string $VendorId
     */
    protected $VendorId = null;

    /**
     * @property string $VendorItemId
     */
    protected $VendorItemId = null;

    /**
     * @property int $ZeroPriceValId
     */
    protected $ZeroPriceValId = null;

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
     * @property string $checksum
     */
    protected $checksum = null;

    /**
     * @property string $processed_at
     */
    protected $processed_at = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplItem' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @param string $BaseUnitOfMeasure
     * @return $this
     */
    public function setBaseUnitOfMeasure($BaseUnitOfMeasure)
    {
        $this->setData( 'BaseUnitOfMeasure', $BaseUnitOfMeasure );
        $this->BaseUnitOfMeasure = $BaseUnitOfMeasure;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUnitOfMeasure()
    {
        return $this->getData( 'BaseUnitOfMeasure' );
    }

    /**
     * @param int $BlockDiscount
     * @return $this
     */
    public function setBlockDiscount($BlockDiscount)
    {
        $this->setData( 'BlockDiscount', $BlockDiscount );
        $this->BlockDiscount = $BlockDiscount;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlockDiscount()
    {
        return $this->getData( 'BlockDiscount' );
    }

    /**
     * @param int $BlockDistribution
     * @return $this
     */
    public function setBlockDistribution($BlockDistribution)
    {
        $this->setData( 'BlockDistribution', $BlockDistribution );
        $this->BlockDistribution = $BlockDistribution;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlockDistribution()
    {
        return $this->getData( 'BlockDistribution' );
    }

    /**
     * @param int $BlockManualPriceChange
     * @return $this
     */
    public function setBlockManualPriceChange($BlockManualPriceChange)
    {
        $this->setData( 'BlockManualPriceChange', $BlockManualPriceChange );
        $this->BlockManualPriceChange = $BlockManualPriceChange;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlockManualPriceChange()
    {
        return $this->getData( 'BlockManualPriceChange' );
    }

    /**
     * @param int $BlockNegativeAdjustment
     * @return $this
     */
    public function setBlockNegativeAdjustment($BlockNegativeAdjustment)
    {
        $this->setData( 'BlockNegativeAdjustment', $BlockNegativeAdjustment );
        $this->BlockNegativeAdjustment = $BlockNegativeAdjustment;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlockNegativeAdjustment()
    {
        return $this->getData( 'BlockNegativeAdjustment' );
    }

    /**
     * @param int $BlockPositiveAdjustment
     * @return $this
     */
    public function setBlockPositiveAdjustment($BlockPositiveAdjustment)
    {
        $this->setData( 'BlockPositiveAdjustment', $BlockPositiveAdjustment );
        $this->BlockPositiveAdjustment = $BlockPositiveAdjustment;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlockPositiveAdjustment()
    {
        return $this->getData( 'BlockPositiveAdjustment' );
    }

    /**
     * @param int $BlockPurchaseReturn
     * @return $this
     */
    public function setBlockPurchaseReturn($BlockPurchaseReturn)
    {
        $this->setData( 'BlockPurchaseReturn', $BlockPurchaseReturn );
        $this->BlockPurchaseReturn = $BlockPurchaseReturn;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlockPurchaseReturn()
    {
        return $this->getData( 'BlockPurchaseReturn' );
    }

    /**
     * @param int $Blocked
     * @return $this
     */
    public function setBlocked($Blocked)
    {
        $this->setData( 'Blocked', $Blocked );
        $this->Blocked = $Blocked;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlocked()
    {
        return $this->getData( 'Blocked' );
    }

    /**
     * @param int $BlockedOnECom
     * @return $this
     */
    public function setBlockedOnECom($BlockedOnECom)
    {
        $this->setData( 'BlockedOnECom', $BlockedOnECom );
        $this->BlockedOnECom = $BlockedOnECom;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlockedOnECom()
    {
        return $this->getData( 'BlockedOnECom' );
    }

    /**
     * @param int $BlockedOnPos
     * @return $this
     */
    public function setBlockedOnPos($BlockedOnPos)
    {
        $this->setData( 'BlockedOnPos', $BlockedOnPos );
        $this->BlockedOnPos = $BlockedOnPos;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlockedOnPos()
    {
        return $this->getData( 'BlockedOnPos' );
    }

    /**
     * @param string $CountryOfOrigin
     * @return $this
     */
    public function setCountryOfOrigin($CountryOfOrigin)
    {
        $this->setData( 'CountryOfOrigin', $CountryOfOrigin );
        $this->CountryOfOrigin = $CountryOfOrigin;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCountryOfOrigin()
    {
        return $this->getData( 'CountryOfOrigin' );
    }

    /**
     * @param int $CrossSellingExists
     * @return $this
     */
    public function setCrossSellingExists($CrossSellingExists)
    {
        $this->setData( 'CrossSellingExists', $CrossSellingExists );
        $this->CrossSellingExists = $CrossSellingExists;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getCrossSellingExists()
    {
        return $this->getData( 'CrossSellingExists' );
    }

    /**
     * @param string $DateBlocked
     * @return $this
     */
    public function setDateBlocked($DateBlocked)
    {
        $this->setData( 'DateBlocked', $DateBlocked );
        $this->DateBlocked = $DateBlocked;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getDateBlocked()
    {
        return $this->getData( 'DateBlocked' );
    }

    /**
     * @param string $DateToActivateItem
     * @return $this
     */
    public function setDateToActivateItem($DateToActivateItem)
    {
        $this->setData( 'DateToActivateItem', $DateToActivateItem );
        $this->DateToActivateItem = $DateToActivateItem;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getDateToActivateItem()
    {
        return $this->getData( 'DateToActivateItem' );
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
     * @param string $Details
     * @return $this
     */
    public function setDetails($Details)
    {
        $this->setData( 'Details', $Details );
        $this->Details = $Details;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getDetails()
    {
        return $this->getData( 'Details' );
    }

    /**
     * @param float $GrossWeight
     * @return $this
     */
    public function setGrossWeight($GrossWeight)
    {
        $this->setData( 'GrossWeight', $GrossWeight );
        $this->GrossWeight = $GrossWeight;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getGrossWeight()
    {
        return $this->getData( 'GrossWeight' );
    }

    /**
     * @param string $nav_id
     * @return $this
     */
    public function setNavId($nav_id)
    {
        $this->setData( 'nav_id', $nav_id );
        $this->nav_id = $nav_id;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getNavId()
    {
        return $this->getData( 'nav_id' );
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
     * @param string $ItemCategoryCode
     * @return $this
     */
    public function setItemCategoryCode($ItemCategoryCode)
    {
        $this->setData( 'ItemCategoryCode', $ItemCategoryCode );
        $this->ItemCategoryCode = $ItemCategoryCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getItemCategoryCode()
    {
        return $this->getData( 'ItemCategoryCode' );
    }

    /**
     * @param string $ItemFamilyCode
     * @return $this
     */
    public function setItemFamilyCode($ItemFamilyCode)
    {
        $this->setData( 'ItemFamilyCode', $ItemFamilyCode );
        $this->ItemFamilyCode = $ItemFamilyCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getItemFamilyCode()
    {
        return $this->getData( 'ItemFamilyCode' );
    }

    /**
     * @param int $KeyingInPrice
     * @return $this
     */
    public function setKeyingInPrice($KeyingInPrice)
    {
        $this->setData( 'KeyingInPrice', $KeyingInPrice );
        $this->KeyingInPrice = $KeyingInPrice;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getKeyingInPrice()
    {
        return $this->getData( 'KeyingInPrice' );
    }

    /**
     * @param int $KeyingInQty
     * @return $this
     */
    public function setKeyingInQty($KeyingInQty)
    {
        $this->setData( 'KeyingInQty', $KeyingInQty );
        $this->KeyingInQty = $KeyingInQty;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getKeyingInQty()
    {
        return $this->getData( 'KeyingInQty' );
    }

    /**
     * @param int $MustKeyInComment
     * @return $this
     */
    public function setMustKeyInComment($MustKeyInComment)
    {
        $this->setData( 'MustKeyInComment', $MustKeyInComment );
        $this->MustKeyInComment = $MustKeyInComment;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getMustKeyInComment()
    {
        return $this->getData( 'MustKeyInComment' );
    }

    /**
     * @param int $NoDiscountAllowed
     * @return $this
     */
    public function setNoDiscountAllowed($NoDiscountAllowed)
    {
        $this->setData( 'NoDiscountAllowed', $NoDiscountAllowed );
        $this->NoDiscountAllowed = $NoDiscountAllowed;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getNoDiscountAllowed()
    {
        return $this->getData( 'NoDiscountAllowed' );
    }

    /**
     * @param string $ProductGroupId
     * @return $this
     */
    public function setProductGroupId($ProductGroupId)
    {
        $this->setData( 'ProductGroupId', $ProductGroupId );
        $this->ProductGroupId = $ProductGroupId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getProductGroupId()
    {
        return $this->getData( 'ProductGroupId' );
    }

    /**
     * @param string $PurchUnitOfMeasure
     * @return $this
     */
    public function setPurchUnitOfMeasure($PurchUnitOfMeasure)
    {
        $this->setData( 'PurchUnitOfMeasure', $PurchUnitOfMeasure );
        $this->PurchUnitOfMeasure = $PurchUnitOfMeasure;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getPurchUnitOfMeasure()
    {
        return $this->getData( 'PurchUnitOfMeasure' );
    }

    /**
     * @param string $SalseUnitOfMeasure
     * @return $this
     */
    public function setSalseUnitOfMeasure($SalseUnitOfMeasure)
    {
        $this->setData( 'SalseUnitOfMeasure', $SalseUnitOfMeasure );
        $this->SalseUnitOfMeasure = $SalseUnitOfMeasure;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getSalseUnitOfMeasure()
    {
        return $this->getData( 'SalseUnitOfMeasure' );
    }

    /**
     * @param int $ScaleItem
     * @return $this
     */
    public function setScaleItem($ScaleItem)
    {
        $this->setData( 'ScaleItem', $ScaleItem );
        $this->ScaleItem = $ScaleItem;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getScaleItem()
    {
        return $this->getData( 'ScaleItem' );
    }

    /**
     * @param string $SeasonCode
     * @return $this
     */
    public function setSeasonCode($SeasonCode)
    {
        $this->setData( 'SeasonCode', $SeasonCode );
        $this->SeasonCode = $SeasonCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getSeasonCode()
    {
        return $this->getData( 'SeasonCode' );
    }

    /**
     * @param string $TaxItemGroupId
     * @return $this
     */
    public function setTaxItemGroupId($TaxItemGroupId)
    {
        $this->setData( 'TaxItemGroupId', $TaxItemGroupId );
        $this->TaxItemGroupId = $TaxItemGroupId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getTaxItemGroupId()
    {
        return $this->getData( 'TaxItemGroupId' );
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
     * @param float $UnitVolume
     * @return $this
     */
    public function setUnitVolume($UnitVolume)
    {
        $this->setData( 'UnitVolume', $UnitVolume );
        $this->UnitVolume = $UnitVolume;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getUnitVolume()
    {
        return $this->getData( 'UnitVolume' );
    }

    /**
     * @param float $UnitsPerParcel
     * @return $this
     */
    public function setUnitsPerParcel($UnitsPerParcel)
    {
        $this->setData( 'UnitsPerParcel', $UnitsPerParcel );
        $this->UnitsPerParcel = $UnitsPerParcel;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getUnitsPerParcel()
    {
        return $this->getData( 'UnitsPerParcel' );
    }

    /**
     * @param string $VendorId
     * @return $this
     */
    public function setVendorId($VendorId)
    {
        $this->setData( 'VendorId', $VendorId );
        $this->VendorId = $VendorId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getVendorId()
    {
        return $this->getData( 'VendorId' );
    }

    /**
     * @param string $VendorItemId
     * @return $this
     */
    public function setVendorItemId($VendorItemId)
    {
        $this->setData( 'VendorItemId', $VendorItemId );
        $this->VendorItemId = $VendorItemId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getVendorItemId()
    {
        return $this->getData( 'VendorItemId' );
    }

    /**
     * @param int $ZeroPriceValId
     * @return $this
     */
    public function setZeroPriceValId($ZeroPriceValId)
    {
        $this->setData( 'ZeroPriceValId', $ZeroPriceValId );
        $this->ZeroPriceValId = $ZeroPriceValId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getZeroPriceValId()
    {
        return $this->getData( 'ZeroPriceValId' );
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

