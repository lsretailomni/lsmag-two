<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\LineType;
use Ls\Omni\Exception\InvalidEnumException;

class OrderLine extends Entity
{
    /**
     * @property float $Amount
     */
    protected $Amount = null;

    /**
     * @property boolean $ClickAndCollectLine
     */
    protected $ClickAndCollectLine = null;

    /**
     * @property float $DiscountAmount
     */
    protected $DiscountAmount = null;

    /**
     * @property float $DiscountPercent
     */
    protected $DiscountPercent = null;

    /**
     * @property boolean $InventoryTransfer
     */
    protected $InventoryTransfer = null;

    /**
     * @property string $ItemDescription
     */
    protected $ItemDescription = null;

    /**
     * @property string $ItemId
     */
    protected $ItemId = null;

    /**
     * @property string $ItemImageId
     */
    protected $ItemImageId = null;

    /**
     * @property int $LineNumber
     */
    protected $LineNumber = null;

    /**
     * @property LineType $LineType
     */
    protected $LineType = null;

    /**
     * @property float $NetAmount
     */
    protected $NetAmount = null;

    /**
     * @property float $NetPrice
     */
    protected $NetPrice = null;

    /**
     * @property string $OrderId
     */
    protected $OrderId = null;

    /**
     * @property float $Price
     */
    protected $Price = null;

    /**
     * @property float $Quantity
     */
    protected $Quantity = null;

    /**
     * @property string $SourcingLocation
     */
    protected $SourcingLocation = null;

    /**
     * @property string $StoreId
     */
    protected $StoreId = null;

    /**
     * @property float $TaxAmount
     */
    protected $TaxAmount = null;

    /**
     * @property string $UomId
     */
    protected $UomId = null;

    /**
     * @property boolean $ValidateTax
     */
    protected $ValidateTax = null;

    /**
     * @property string $VariantDescription
     */
    protected $VariantDescription = null;

    /**
     * @property string $VariantId
     */
    protected $VariantId = null;

    /**
     * @property boolean $VendorSourcing
     */
    protected $VendorSourcing = null;

    /**
     * @param float $Amount
     * @return $this
     */
    public function setAmount($Amount)
    {
        $this->Amount = $Amount;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->Amount;
    }

    /**
     * @param boolean $ClickAndCollectLine
     * @return $this
     */
    public function setClickAndCollectLine($ClickAndCollectLine)
    {
        $this->ClickAndCollectLine = $ClickAndCollectLine;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getClickAndCollectLine()
    {
        return $this->ClickAndCollectLine;
    }

    /**
     * @param float $DiscountAmount
     * @return $this
     */
    public function setDiscountAmount($DiscountAmount)
    {
        $this->DiscountAmount = $DiscountAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->DiscountAmount;
    }

    /**
     * @param float $DiscountPercent
     * @return $this
     */
    public function setDiscountPercent($DiscountPercent)
    {
        $this->DiscountPercent = $DiscountPercent;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountPercent()
    {
        return $this->DiscountPercent;
    }

    /**
     * @param boolean $InventoryTransfer
     * @return $this
     */
    public function setInventoryTransfer($InventoryTransfer)
    {
        $this->InventoryTransfer = $InventoryTransfer;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getInventoryTransfer()
    {
        return $this->InventoryTransfer;
    }

    /**
     * @param string $ItemDescription
     * @return $this
     */
    public function setItemDescription($ItemDescription)
    {
        $this->ItemDescription = $ItemDescription;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemDescription()
    {
        return $this->ItemDescription;
    }

    /**
     * @param string $ItemId
     * @return $this
     */
    public function setItemId($ItemId)
    {
        $this->ItemId = $ItemId;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->ItemId;
    }

    /**
     * @param string $ItemImageId
     * @return $this
     */
    public function setItemImageId($ItemImageId)
    {
        $this->ItemImageId = $ItemImageId;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemImageId()
    {
        return $this->ItemImageId;
    }

    /**
     * @param int $LineNumber
     * @return $this
     */
    public function setLineNumber($LineNumber)
    {
        $this->LineNumber = $LineNumber;
        return $this;
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->LineNumber;
    }

    /**
     * @param LineType|string $LineType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setLineType($LineType)
    {
        if ( ! $LineType instanceof LineType ) {
            if ( LineType::isValid( $LineType ) )
                $LineType = new LineType( $LineType );
            elseif ( LineType::isValidKey( $LineType ) )
                $LineType = new LineType( constant( "LineType::$LineType" ) );
            elseif ( ! $LineType instanceof LineType )
                throw new InvalidEnumException();
        }
        $this->LineType = $LineType->getValue();

        return $this;
    }

    /**
     * @return LineType
     */
    public function getLineType()
    {
        return $this->LineType;
    }

    /**
     * @param float $NetAmount
     * @return $this
     */
    public function setNetAmount($NetAmount)
    {
        $this->NetAmount = $NetAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getNetAmount()
    {
        return $this->NetAmount;
    }

    /**
     * @param float $NetPrice
     * @return $this
     */
    public function setNetPrice($NetPrice)
    {
        $this->NetPrice = $NetPrice;
        return $this;
    }

    /**
     * @return float
     */
    public function getNetPrice()
    {
        return $this->NetPrice;
    }

    /**
     * @param string $OrderId
     * @return $this
     */
    public function setOrderId($OrderId)
    {
        $this->OrderId = $OrderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->OrderId;
    }

    /**
     * @param float $Price
     * @return $this
     */
    public function setPrice($Price)
    {
        $this->Price = $Price;
        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->Price;
    }

    /**
     * @param float $Quantity
     * @return $this
     */
    public function setQuantity($Quantity)
    {
        $this->Quantity = $Quantity;
        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->Quantity;
    }

    /**
     * @param string $SourcingLocation
     * @return $this
     */
    public function setSourcingLocation($SourcingLocation)
    {
        $this->SourcingLocation = $SourcingLocation;
        return $this;
    }

    /**
     * @return string
     */
    public function getSourcingLocation()
    {
        return $this->SourcingLocation;
    }

    /**
     * @param string $StoreId
     * @return $this
     */
    public function setStoreId($StoreId)
    {
        $this->StoreId = $StoreId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->StoreId;
    }

    /**
     * @param float $TaxAmount
     * @return $this
     */
    public function setTaxAmount($TaxAmount)
    {
        $this->TaxAmount = $TaxAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getTaxAmount()
    {
        return $this->TaxAmount;
    }

    /**
     * @param string $UomId
     * @return $this
     */
    public function setUomId($UomId)
    {
        $this->UomId = $UomId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUomId()
    {
        return $this->UomId;
    }

    /**
     * @param boolean $ValidateTax
     * @return $this
     */
    public function setValidateTax($ValidateTax)
    {
        $this->ValidateTax = $ValidateTax;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getValidateTax()
    {
        return $this->ValidateTax;
    }

    /**
     * @param string $VariantDescription
     * @return $this
     */
    public function setVariantDescription($VariantDescription)
    {
        $this->VariantDescription = $VariantDescription;
        return $this;
    }

    /**
     * @return string
     */
    public function getVariantDescription()
    {
        return $this->VariantDescription;
    }

    /**
     * @param string $VariantId
     * @return $this
     */
    public function setVariantId($VariantId)
    {
        $this->VariantId = $VariantId;
        return $this;
    }

    /**
     * @return string
     */
    public function getVariantId()
    {
        return $this->VariantId;
    }

    /**
     * @param boolean $VendorSourcing
     * @return $this
     */
    public function setVendorSourcing($VendorSourcing)
    {
        $this->VendorSourcing = $VendorSourcing;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getVendorSourcing()
    {
        return $this->VendorSourcing;
    }
}

