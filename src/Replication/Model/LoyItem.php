<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\LoyItemInterface;

class LoyItem extends AbstractModel implements LoyItemInterface, IdentityInterface
{
    public const CACHE_TAG = 'ls_replication_loy_item';

    protected $_cacheTag = 'ls_replication_loy_item';

    protected $_eventPrefix = 'ls_replication_loy_item';

    /**
     * @property ArrayOfImageView $Images
     */
    protected $Images = null;

    /**
     * @property ArrayOfRetailAttribute $ItemAttributes
     */
    protected $ItemAttributes = null;

    /**
     * @property ArrayOfItemLocation $Locations
     */
    protected $Locations = null;

    /**
     * @property ArrayOfItemModifier $Modifiers
     */
    protected $Modifiers = null;

    /**
     * @property ArrayOfPrice $Prices
     */
    protected $Prices = null;

    /**
     * @property ArrayOfItemRecipe $Recipes
     */
    protected $Recipes = null;

    /**
     * @property ArrayOfUnitOfMeasure $UnitOfMeasures
     */
    protected $UnitOfMeasures = null;

    /**
     * @property ArrayOfVariantExt $VariantsExt
     */
    protected $VariantsExt = null;

    /**
     * @property ArrayOfVariantRegistration $VariantsRegistration
     */
    protected $VariantsRegistration = null;

    /**
     * @property boolean $AllowedToSell
     */
    protected $AllowedToSell = null;

    /**
     * @property boolean $BlockDiscount
     */
    protected $BlockDiscount = null;

    /**
     * @property boolean $BlockManualPriceChange
     */
    protected $BlockManualPriceChange = null;

    /**
     * @property boolean $Blocked
     */
    protected $Blocked = null;

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
     * @property string $ItemTrackingCode
     */
    protected $ItemTrackingCode = null;

    /**
     * @property string $Price
     */
    protected $Price = null;

    /**
     * @property string $ProductGroupId
     */
    protected $ProductGroupId = null;

    /**
     * @property string $SalesUomId
     */
    protected $SalesUomId = null;

    /**
     * @property boolean $ScaleItem
     */
    protected $ScaleItem = null;

    /**
     * @property string $SeasonCode
     */
    protected $SeasonCode = null;

    /**
     * @property UnitOfMeasure $SelectedUnitOfMeasure
     */
    protected $SelectedUnitOfMeasure = null;

    /**
     * @property VariantRegistration $SelectedVariant
     */
    protected $SelectedVariant = null;

    /**
     * @property string $SpecialGroups
     */
    protected $SpecialGroups = null;

    /**
     * @property string $TariffNo
     */
    protected $TariffNo = null;

    /**
     * @property float $UnitVolume
     */
    protected $UnitVolume = null;

    /**
     * @property float $UnitsPerParcel
     */
    protected $UnitsPerParcel = null;

    /**
     * @property string $nav_id
     */
    protected $nav_id = null;

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
        $this->_init( 'Ls\Replication\Model\ResourceModel\LoyItem' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @param ArrayOfImageView $Images
     * @return $this
     */
    public function setImages($Images)
    {
        $this->setData( 'Images', $Images );
        $this->Images = $Images;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfImageView
     */
    public function getImages()
    {
        return $this->getData( 'Images' );
    }

    /**
     * @param ArrayOfRetailAttribute $ItemAttributes
     * @return $this
     */
    public function setItemAttributes($ItemAttributes)
    {
        $this->setData( 'ItemAttributes', $ItemAttributes );
        $this->ItemAttributes = $ItemAttributes;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfRetailAttribute
     */
    public function getItemAttributes()
    {
        return $this->getData( 'ItemAttributes' );
    }

    /**
     * @param ArrayOfItemLocation $Locations
     * @return $this
     */
    public function setLocations($Locations)
    {
        $this->setData( 'Locations', $Locations );
        $this->Locations = $Locations;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfItemLocation
     */
    public function getLocations()
    {
        return $this->getData( 'Locations' );
    }

    /**
     * @param ArrayOfItemModifier $Modifiers
     * @return $this
     */
    public function setModifiers($Modifiers)
    {
        $this->setData( 'Modifiers', $Modifiers );
        $this->Modifiers = $Modifiers;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfItemModifier
     */
    public function getModifiers()
    {
        return $this->getData( 'Modifiers' );
    }

    /**
     * @param ArrayOfPrice $Prices
     * @return $this
     */
    public function setPrices($Prices)
    {
        $this->setData( 'Prices', $Prices );
        $this->Prices = $Prices;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfPrice
     */
    public function getPrices()
    {
        return $this->getData( 'Prices' );
    }

    /**
     * @param ArrayOfItemRecipe $Recipes
     * @return $this
     */
    public function setRecipes($Recipes)
    {
        $this->setData( 'Recipes', $Recipes );
        $this->Recipes = $Recipes;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfItemRecipe
     */
    public function getRecipes()
    {
        return $this->getData( 'Recipes' );
    }

    /**
     * @param ArrayOfUnitOfMeasure $UnitOfMeasures
     * @return $this
     */
    public function setUnitOfMeasures($UnitOfMeasures)
    {
        $this->setData( 'UnitOfMeasures', $UnitOfMeasures );
        $this->UnitOfMeasures = $UnitOfMeasures;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfUnitOfMeasure
     */
    public function getUnitOfMeasures()
    {
        return $this->getData( 'UnitOfMeasures' );
    }

    /**
     * @param ArrayOfVariantExt $VariantsExt
     * @return $this
     */
    public function setVariantsExt($VariantsExt)
    {
        $this->setData( 'VariantsExt', $VariantsExt );
        $this->VariantsExt = $VariantsExt;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfVariantExt
     */
    public function getVariantsExt()
    {
        return $this->getData( 'VariantsExt' );
    }

    /**
     * @param ArrayOfVariantRegistration $VariantsRegistration
     * @return $this
     */
    public function setVariantsRegistration($VariantsRegistration)
    {
        $this->setData( 'VariantsRegistration', $VariantsRegistration );
        $this->VariantsRegistration = $VariantsRegistration;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfVariantRegistration
     */
    public function getVariantsRegistration()
    {
        return $this->getData( 'VariantsRegistration' );
    }

    /**
     * @param boolean $AllowedToSell
     * @return $this
     */
    public function setAllowedToSell($AllowedToSell)
    {
        $this->setData( 'AllowedToSell', $AllowedToSell );
        $this->AllowedToSell = $AllowedToSell;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAllowedToSell()
    {
        return $this->getData( 'AllowedToSell' );
    }

    /**
     * @param boolean $BlockDiscount
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
     * @return boolean
     */
    public function getBlockDiscount()
    {
        return $this->getData( 'BlockDiscount' );
    }

    /**
     * @param boolean $BlockManualPriceChange
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
     * @return boolean
     */
    public function getBlockManualPriceChange()
    {
        return $this->getData( 'BlockManualPriceChange' );
    }

    /**
     * @param boolean $Blocked
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
     * @return boolean
     */
    public function getBlocked()
    {
        return $this->getData( 'Blocked' );
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
     * @param string $ItemTrackingCode
     * @return $this
     */
    public function setItemTrackingCode($ItemTrackingCode)
    {
        $this->setData( 'ItemTrackingCode', $ItemTrackingCode );
        $this->ItemTrackingCode = $ItemTrackingCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getItemTrackingCode()
    {
        return $this->getData( 'ItemTrackingCode' );
    }

    /**
     * @param string $Price
     * @return $this
     */
    public function setPrice($Price)
    {
        $this->setData( 'Price', $Price );
        $this->Price = $Price;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->getData( 'Price' );
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
     * @param string $SalesUomId
     * @return $this
     */
    public function setSalesUomId($SalesUomId)
    {
        $this->setData( 'SalesUomId', $SalesUomId );
        $this->SalesUomId = $SalesUomId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getSalesUomId()
    {
        return $this->getData( 'SalesUomId' );
    }

    /**
     * @param boolean $ScaleItem
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
     * @return boolean
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
     * @param UnitOfMeasure $SelectedUnitOfMeasure
     * @return $this
     */
    public function setSelectedUnitOfMeasure($SelectedUnitOfMeasure)
    {
        $this->setData( 'SelectedUnitOfMeasure', $SelectedUnitOfMeasure );
        $this->SelectedUnitOfMeasure = $SelectedUnitOfMeasure;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return UnitOfMeasure
     */
    public function getSelectedUnitOfMeasure()
    {
        return $this->getData( 'SelectedUnitOfMeasure' );
    }

    /**
     * @param VariantRegistration $SelectedVariant
     * @return $this
     */
    public function setSelectedVariant($SelectedVariant)
    {
        $this->setData( 'SelectedVariant', $SelectedVariant );
        $this->SelectedVariant = $SelectedVariant;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return VariantRegistration
     */
    public function getSelectedVariant()
    {
        return $this->getData( 'SelectedVariant' );
    }

    /**
     * @param string $SpecialGroups
     * @return $this
     */
    public function setSpecialGroups($SpecialGroups)
    {
        $this->setData( 'SpecialGroups', $SpecialGroups );
        $this->SpecialGroups = $SpecialGroups;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getSpecialGroups()
    {
        return $this->getData( 'SpecialGroups' );
    }

    /**
     * @param string $TariffNo
     * @return $this
     */
    public function setTariffNo($TariffNo)
    {
        $this->setData( 'TariffNo', $TariffNo );
        $this->TariffNo = $TariffNo;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getTariffNo()
    {
        return $this->getData( 'TariffNo' );
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

