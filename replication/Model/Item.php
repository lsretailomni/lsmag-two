<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ItemInterface;

class Item extends AbstractModel implements ItemInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_item';

    protected $_cacheTag = 'lsr_replication_item';

    protected $_eventPrefix = 'lsr_replication_item';

    protected $AllowedToSell = null;

    protected $Description = null;

    protected $Details = null;

    protected $Id = null;

    protected $Images = null;

    protected $ItemAttributes = null;

    protected $Price = null;

    protected $ProductGroupId = null;

    protected $SalesUomId = null;

    protected $UOMs = null;

    protected $Variants = null;

    protected $VariantsExt = null;

    protected $VariantsRegistration = null;

    protected $BaseUOM = null;

    protected $Del = null;

    protected $FullDescription = null;

    protected $ProductGroupCode = null;

    protected $PurchUOM = null;

    protected $SalesUOM = null;

    protected $ScaleItem = null;

    protected $VendorId = null;

    protected $VendorItemId = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Item' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    public function setAllowedToSell($AllowedToSell)
    {
        $this->AllowedToSell = $AllowedToSell;
        return $this;
    }

    public function getAllowedToSell()
    {
        return $this->AllowedToSell;
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

    public function setDetails($Details)
    {
        $this->Details = $Details;
        return $this;
    }

    public function getDetails()
    {
        return $this->Details;
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

    public function setImages($Images)
    {
        $this->Images = $Images;
        return $this;
    }

    public function getImages()
    {
        return $this->Images;
    }

    public function setItemAttributes($ItemAttributes)
    {
        $this->ItemAttributes = $ItemAttributes;
        return $this;
    }

    public function getItemAttributes()
    {
        return $this->ItemAttributes;
    }

    public function setPrice($Price)
    {
        $this->Price = $Price;
        return $this;
    }

    public function getPrice()
    {
        return $this->Price;
    }

    public function setProductGroupId($ProductGroupId)
    {
        $this->ProductGroupId = $ProductGroupId;
        return $this;
    }

    public function getProductGroupId()
    {
        return $this->ProductGroupId;
    }

    public function setSalesUomId($SalesUomId)
    {
        $this->SalesUomId = $SalesUomId;
        return $this;
    }

    public function getSalesUomId()
    {
        return $this->SalesUomId;
    }

    public function setUOMs($UOMs)
    {
        $this->UOMs = $UOMs;
        return $this;
    }

    public function getUOMs()
    {
        return $this->UOMs;
    }

    public function setVariants($Variants)
    {
        $this->Variants = $Variants;
        return $this;
    }

    public function getVariants()
    {
        return $this->Variants;
    }

    public function setVariantsExt($VariantsExt)
    {
        $this->VariantsExt = $VariantsExt;
        return $this;
    }

    public function getVariantsExt()
    {
        return $this->VariantsExt;
    }

    public function setVariantsRegistration($VariantsRegistration)
    {
        $this->VariantsRegistration = $VariantsRegistration;
        return $this;
    }

    public function getVariantsRegistration()
    {
        return $this->VariantsRegistration;
    }

    public function setBaseUOM($BaseUOM)
    {
        $this->BaseUOM = $BaseUOM;
        return $this;
    }

    public function getBaseUOM()
    {
        return $this->BaseUOM;
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

    public function setFullDescription($FullDescription)
    {
        $this->FullDescription = $FullDescription;
        return $this;
    }

    public function getFullDescription()
    {
        return $this->FullDescription;
    }

    public function setProductGroupCode($ProductGroupCode)
    {
        $this->ProductGroupCode = $ProductGroupCode;
        return $this;
    }

    public function getProductGroupCode()
    {
        return $this->ProductGroupCode;
    }

    public function setPurchUOM($PurchUOM)
    {
        $this->PurchUOM = $PurchUOM;
        return $this;
    }

    public function getPurchUOM()
    {
        return $this->PurchUOM;
    }

    public function setSalesUOM($SalesUOM)
    {
        $this->SalesUOM = $SalesUOM;
        return $this;
    }

    public function getSalesUOM()
    {
        return $this->SalesUOM;
    }

    public function setScaleItem($ScaleItem)
    {
        $this->ScaleItem = $ScaleItem;
        return $this;
    }

    public function getScaleItem()
    {
        return $this->ScaleItem;
    }

    public function setVendorId($VendorId)
    {
        $this->VendorId = $VendorId;
        return $this;
    }

    public function getVendorId()
    {
        return $this->VendorId;
    }

    public function setVendorItemId($VendorItemId)
    {
        $this->VendorItemId = $VendorItemId;
        return $this;
    }

    public function getVendorItemId()
    {
        return $this->VendorItemId;
    }


}

