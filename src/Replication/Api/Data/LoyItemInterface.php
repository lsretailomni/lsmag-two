<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Api\Data;

interface LoyItemInterface
{

    /**
     * @param ArrayOfImageView $Images
     * @return $this
     */
    public function setImages($Images);

    /**
     * @return ArrayOfImageView
     */
    public function getImages();

    /**
     * @param ArrayOfRetailAttribute $ItemAttributes
     * @return $this
     */
    public function setItemAttributes($ItemAttributes);

    /**
     * @return ArrayOfRetailAttribute
     */
    public function getItemAttributes();

    /**
     * @param ArrayOfItemLocation $Locations
     * @return $this
     */
    public function setLocations($Locations);

    /**
     * @return ArrayOfItemLocation
     */
    public function getLocations();

    /**
     * @param ArrayOfItemModifier $Modifiers
     * @return $this
     */
    public function setModifiers($Modifiers);

    /**
     * @return ArrayOfItemModifier
     */
    public function getModifiers();

    /**
     * @param ArrayOfPrice $Prices
     * @return $this
     */
    public function setPrices($Prices);

    /**
     * @return ArrayOfPrice
     */
    public function getPrices();

    /**
     * @param ArrayOfItemRecipe $Recipes
     * @return $this
     */
    public function setRecipes($Recipes);

    /**
     * @return ArrayOfItemRecipe
     */
    public function getRecipes();

    /**
     * @param ArrayOfUnitOfMeasure $UnitOfMeasures
     * @return $this
     */
    public function setUnitOfMeasures($UnitOfMeasures);

    /**
     * @return ArrayOfUnitOfMeasure
     */
    public function getUnitOfMeasures();

    /**
     * @param ArrayOfVariantExt $VariantsExt
     * @return $this
     */
    public function setVariantsExt($VariantsExt);

    /**
     * @return ArrayOfVariantExt
     */
    public function getVariantsExt();

    /**
     * @param ArrayOfVariantRegistration $VariantsRegistration
     * @return $this
     */
    public function setVariantsRegistration($VariantsRegistration);

    /**
     * @return ArrayOfVariantRegistration
     */
    public function getVariantsRegistration();

    /**
     * @param boolean $AllowedToSell
     * @return $this
     */
    public function setAllowedToSell($AllowedToSell);

    /**
     * @return boolean
     */
    public function getAllowedToSell();

    /**
     * @param boolean $BlockDiscount
     * @return $this
     */
    public function setBlockDiscount($BlockDiscount);

    /**
     * @return boolean
     */
    public function getBlockDiscount();

    /**
     * @param boolean $BlockManualPriceChange
     * @return $this
     */
    public function setBlockManualPriceChange($BlockManualPriceChange);

    /**
     * @return boolean
     */
    public function getBlockManualPriceChange();

    /**
     * @param boolean $Blocked
     * @return $this
     */
    public function setBlocked($Blocked);

    /**
     * @return boolean
     */
    public function getBlocked();

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $Details
     * @return $this
     */
    public function setDetails($Details);

    /**
     * @return string
     */
    public function getDetails();

    /**
     * @param float $GrossWeight
     * @return $this
     */
    public function setGrossWeight($GrossWeight);

    /**
     * @return float
     */
    public function getGrossWeight();

    /**
     * @param boolean $IsDeleted
     * @return $this
     */
    public function setIsDeleted($IsDeleted);

    /**
     * @return boolean
     */
    public function getIsDeleted();

    /**
     * @param string $ItemCategoryCode
     * @return $this
     */
    public function setItemCategoryCode($ItemCategoryCode);

    /**
     * @return string
     */
    public function getItemCategoryCode();

    /**
     * @param string $ItemFamilyCode
     * @return $this
     */
    public function setItemFamilyCode($ItemFamilyCode);

    /**
     * @return string
     */
    public function getItemFamilyCode();

    /**
     * @param string $Price
     * @return $this
     */
    public function setPrice($Price);

    /**
     * @return string
     */
    public function getPrice();

    /**
     * @param string $ProductGroupId
     * @return $this
     */
    public function setProductGroupId($ProductGroupId);

    /**
     * @return string
     */
    public function getProductGroupId();

    /**
     * @param string $SalesUomId
     * @return $this
     */
    public function setSalesUomId($SalesUomId);

    /**
     * @return string
     */
    public function getSalesUomId();

    /**
     * @param boolean $ScaleItem
     * @return $this
     */
    public function setScaleItem($ScaleItem);

    /**
     * @return boolean
     */
    public function getScaleItem();

    /**
     * @param string $SeasonCode
     * @return $this
     */
    public function setSeasonCode($SeasonCode);

    /**
     * @return string
     */
    public function getSeasonCode();

    /**
     * @param UnitOfMeasure $SelectedUnitOfMeasure
     * @return $this
     */
    public function setSelectedUnitOfMeasure($SelectedUnitOfMeasure);

    /**
     * @return UnitOfMeasure
     */
    public function getSelectedUnitOfMeasure();

    /**
     * @param VariantRegistration $SelectedVariant
     * @return $this
     */
    public function setSelectedVariant($SelectedVariant);

    /**
     * @return VariantRegistration
     */
    public function getSelectedVariant();

    /**
     * @param float $UnitVolume
     * @return $this
     */
    public function setUnitVolume($UnitVolume);

    /**
     * @return float
     */
    public function getUnitVolume();

    /**
     * @param float $UnitsPerParcel
     * @return $this
     */
    public function setUnitsPerParcel($UnitsPerParcel);

    /**
     * @return float
     */
    public function getUnitsPerParcel();

    /**
     * @param string $nav_id
     * @return $this
     */
    public function setNavId($nav_id);

    /**
     * @return string
     */
    public function getNavId();

    /**
     * @param boolean $processed
     * @return $this
     */
    public function setProcessed($processed);

    /**
     * @return boolean
     */
    public function getProcessed();

    /**
     * @param boolean $is_updated
     * @return $this
     */
    public function setIsUpdated($is_updated);

    /**
     * @return boolean
     */
    public function getIsUpdated();

    /**
     * @param boolean $is_failed
     * @return $this
     */
    public function setIsFailed($is_failed);

    /**
     * @return boolean
     */
    public function getIsFailed();

    /**
     * @param string $created_at
     * @return $this
     */
    public function setCreatedAt($created_at);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $updated_at
     * @return $this
     */
    public function setUpdatedAt($updated_at);

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $checksum
     * @return $this
     */
    public function setChecksum($checksum);

    /**
     * @return string
     */
    public function getChecksum();

    /**
     * @param string $processed_at
     * @return $this
     */
    public function setProcessedAt($processed_at);

    /**
     * @return string
     */
    public function getProcessedAt();


}

