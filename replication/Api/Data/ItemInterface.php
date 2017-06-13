<?php

namespace Ls\Replication\Api\Data;

interface ItemInterface
{

    /**
     * @return boolean
     */
    public function setAllowedToSell($AllowedToSell);
    public function getAllowedToSell();
    /**
     * @return string
     */
    public function setDescription($Description);
    public function getDescription();
    /**
     * @return string
     */
    public function setDetails($Details);
    public function getDetails();
    /**
     * @return string
     */
    public function setId($Id);
    public function getId();
    /**
     * @return ArrayOfImageView
     */
    public function setImages($Images);
    public function getImages();
    /**
     * @return ArrayOfAttribute
     */
    public function setItemAttributes($ItemAttributes);
    public function getItemAttributes();
    /**
     * @return float
     */
    public function setPrice($Price);
    public function getPrice();
    /**
     * @return string
     */
    public function setProductGroupId($ProductGroupId);
    public function getProductGroupId();
    /**
     * @return string
     */
    public function setSalesUomId($SalesUomId);
    public function getSalesUomId();
    /**
     * @return ArrayOfUOM
     */
    public function setUOMs($UOMs);
    public function getUOMs();
    /**
     * @return ArrayOfVariant
     */
    public function setVariants($Variants);
    public function getVariants();
    /**
     * @return ArrayOfVariantExt
     */
    public function setVariantsExt($VariantsExt);
    public function getVariantsExt();
    /**
     * @return ArrayOfVariantRegistration
     */
    public function setVariantsRegistration($VariantsRegistration);
    public function getVariantsRegistration();
    /**
     * @return string
     */
    public function setBaseUOM($BaseUOM);
    public function getBaseUOM();
    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return string
     */
    public function setFullDescription($FullDescription);
    public function getFullDescription();
    /**
     * @return string
     */
    public function setProductGroupCode($ProductGroupCode);
    public function getProductGroupCode();
    /**
     * @return string
     */
    public function setPurchUOM($PurchUOM);
    public function getPurchUOM();
    /**
     * @return string
     */
    public function setSalesUOM($SalesUOM);
    public function getSalesUOM();
    /**
     * @return int
     */
    public function setScaleItem($ScaleItem);
    public function getScaleItem();
    /**
     * @return string
     */
    public function setVendorId($VendorId);
    public function getVendorId();
    /**
     * @return string
     */
    public function setVendorItemId($VendorItemId);
    public function getVendorItemId();

}

