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

    /**
     * @return $this
     */
    public function setAllowedToSell($AllowedToSell)
    {
        $this->setData( 'AllowedToSell', $AllowedToSell );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getAllowedToSell()
    {
        return $this->AllowedToSell;
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
    public function setDetails($Details)
    {
        $this->setData( 'Details', $Details );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDetails()
    {
        return $this->Details;
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
    public function setImages($Images)
    {
        $this->setData( 'Images', $Images );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getImages()
    {
        return $this->Images;
    }

    /**
     * @return $this
     */
    public function setItemAttributes($ItemAttributes)
    {
        $this->setData( 'ItemAttributes', $ItemAttributes );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getItemAttributes()
    {
        return $this->ItemAttributes;
    }

    /**
     * @return $this
     */
    public function setPrice($Price)
    {
        $this->setData( 'Price', $Price );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getPrice()
    {
        return $this->Price;
    }

    /**
     * @return $this
     */
    public function setProductGroupId($ProductGroupId)
    {
        $this->setData( 'ProductGroupId', $ProductGroupId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getProductGroupId()
    {
        return $this->ProductGroupId;
    }

    /**
     * @return $this
     */
    public function setSalesUomId($SalesUomId)
    {
        $this->setData( 'SalesUomId', $SalesUomId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getSalesUomId()
    {
        return $this->SalesUomId;
    }

    /**
     * @return $this
     */
    public function setUOMs($UOMs)
    {
        $this->setData( 'UOMs', $UOMs );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getUOMs()
    {
        return $this->UOMs;
    }

    /**
     * @return $this
     */
    public function setVariants($Variants)
    {
        $this->setData( 'Variants', $Variants );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVariants()
    {
        return $this->Variants;
    }

    /**
     * @return $this
     */
    public function setVariantsExt($VariantsExt)
    {
        $this->setData( 'VariantsExt', $VariantsExt );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVariantsExt()
    {
        return $this->VariantsExt;
    }

    /**
     * @return $this
     */
    public function setVariantsRegistration($VariantsRegistration)
    {
        $this->setData( 'VariantsRegistration', $VariantsRegistration );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVariantsRegistration()
    {
        return $this->VariantsRegistration;
    }

    /**
     * @return $this
     */
    public function setBaseUOM($BaseUOM)
    {
        $this->setData( 'BaseUOM', $BaseUOM );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getBaseUOM()
    {
        return $this->BaseUOM;
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
    public function setFullDescription($FullDescription)
    {
        $this->setData( 'FullDescription', $FullDescription );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getFullDescription()
    {
        return $this->FullDescription;
    }

    /**
     * @return $this
     */
    public function setProductGroupCode($ProductGroupCode)
    {
        $this->setData( 'ProductGroupCode', $ProductGroupCode );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getProductGroupCode()
    {
        return $this->ProductGroupCode;
    }

    /**
     * @return $this
     */
    public function setPurchUOM($PurchUOM)
    {
        $this->setData( 'PurchUOM', $PurchUOM );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getPurchUOM()
    {
        return $this->PurchUOM;
    }

    /**
     * @return $this
     */
    public function setSalesUOM($SalesUOM)
    {
        $this->setData( 'SalesUOM', $SalesUOM );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getSalesUOM()
    {
        return $this->SalesUOM;
    }

    /**
     * @return $this
     */
    public function setScaleItem($ScaleItem)
    {
        $this->setData( 'ScaleItem', $ScaleItem );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getScaleItem()
    {
        return $this->ScaleItem;
    }

    /**
     * @return $this
     */
    public function setVendorId($VendorId)
    {
        $this->setData( 'VendorId', $VendorId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVendorId()
    {
        return $this->VendorId;
    }

    /**
     * @return $this
     */
    public function setVendorItemId($VendorItemId)
    {
        $this->setData( 'VendorItemId', $VendorItemId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVendorItemId()
    {
        return $this->VendorItemId;
    }


}

