<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\SubLineType;
use Ls\Omni\Exception\InvalidEnumException;

class OrderHospSubLine
{
    /**
     * @property float $Amount
     */
    protected $Amount = null;

    /**
     * @property string $DealCode
     */
    protected $DealCode = null;

    /**
     * @property int $DealLineId
     */
    protected $DealLineId = null;

    /**
     * @property int $DealModifierLineId
     */
    protected $DealModifierLineId = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property float $DiscountAmount
     */
    protected $DiscountAmount = null;

    /**
     * @property float $DiscountPercent
     */
    protected $DiscountPercent = null;

    /**
     * @property string $ItemId
     */
    protected $ItemId = null;

    /**
     * @property int $LineNumber
     */
    protected $LineNumber = null;

    /**
     * @property float $ManualDiscountAmount
     */
    protected $ManualDiscountAmount = null;

    /**
     * @property float $ManualDiscountPercent
     */
    protected $ManualDiscountPercent = null;

    /**
     * @property string $ModifierGroupCode
     */
    protected $ModifierGroupCode = null;

    /**
     * @property string $ModifierSubCode
     */
    protected $ModifierSubCode = null;

    /**
     * @property float $NetAmount
     */
    protected $NetAmount = null;

    /**
     * @property float $NetPrice
     */
    protected $NetPrice = null;

    /**
     * @property int $ParentSubLineId
     */
    protected $ParentSubLineId = null;

    /**
     * @property float $Price
     */
    protected $Price = null;

    /**
     * @property boolean $PriceReductionOnExclusion
     */
    protected $PriceReductionOnExclusion = null;

    /**
     * @property float $Quantity
     */
    protected $Quantity = null;

    /**
     * @property float $TAXAmount
     */
    protected $TAXAmount = null;

    /**
     * @property SubLineType $Type
     */
    protected $Type = null;

    /**
     * @property string $Uom
     */
    protected $Uom = null;

    /**
     * @property string $VariantDescription
     */
    protected $VariantDescription = null;

    /**
     * @property string $VariantId
     */
    protected $VariantId = null;

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
     * @param string $DealCode
     * @return $this
     */
    public function setDealCode($DealCode)
    {
        $this->DealCode = $DealCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getDealCode()
    {
        return $this->DealCode;
    }

    /**
     * @param int $DealLineId
     * @return $this
     */
    public function setDealLineId($DealLineId)
    {
        $this->DealLineId = $DealLineId;
        return $this;
    }

    /**
     * @return int
     */
    public function getDealLineId()
    {
        return $this->DealLineId;
    }

    /**
     * @param int $DealModifierLineId
     * @return $this
     */
    public function setDealModifierLineId($DealModifierLineId)
    {
        $this->DealModifierLineId = $DealModifierLineId;
        return $this;
    }

    /**
     * @return int
     */
    public function getDealModifierLineId()
    {
        return $this->DealModifierLineId;
    }

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
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
     * @param float $ManualDiscountAmount
     * @return $this
     */
    public function setManualDiscountAmount($ManualDiscountAmount)
    {
        $this->ManualDiscountAmount = $ManualDiscountAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getManualDiscountAmount()
    {
        return $this->ManualDiscountAmount;
    }

    /**
     * @param float $ManualDiscountPercent
     * @return $this
     */
    public function setManualDiscountPercent($ManualDiscountPercent)
    {
        $this->ManualDiscountPercent = $ManualDiscountPercent;
        return $this;
    }

    /**
     * @return float
     */
    public function getManualDiscountPercent()
    {
        return $this->ManualDiscountPercent;
    }

    /**
     * @param string $ModifierGroupCode
     * @return $this
     */
    public function setModifierGroupCode($ModifierGroupCode)
    {
        $this->ModifierGroupCode = $ModifierGroupCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getModifierGroupCode()
    {
        return $this->ModifierGroupCode;
    }

    /**
     * @param string $ModifierSubCode
     * @return $this
     */
    public function setModifierSubCode($ModifierSubCode)
    {
        $this->ModifierSubCode = $ModifierSubCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getModifierSubCode()
    {
        return $this->ModifierSubCode;
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
     * @param int $ParentSubLineId
     * @return $this
     */
    public function setParentSubLineId($ParentSubLineId)
    {
        $this->ParentSubLineId = $ParentSubLineId;
        return $this;
    }

    /**
     * @return int
     */
    public function getParentSubLineId()
    {
        return $this->ParentSubLineId;
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
     * @param boolean $PriceReductionOnExclusion
     * @return $this
     */
    public function setPriceReductionOnExclusion($PriceReductionOnExclusion)
    {
        $this->PriceReductionOnExclusion = $PriceReductionOnExclusion;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getPriceReductionOnExclusion()
    {
        return $this->PriceReductionOnExclusion;
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
     * @param float $TAXAmount
     * @return $this
     */
    public function setTAXAmount($TAXAmount)
    {
        $this->TAXAmount = $TAXAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getTAXAmount()
    {
        return $this->TAXAmount;
    }

    /**
     * @param SubLineType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof SubLineType ) {
            if ( SubLineType::isValid( $Type ) )
                $Type = new SubLineType( $Type );
            elseif ( SubLineType::isValidKey( $Type ) )
                $Type = new SubLineType( constant( "SubLineType::$Type" ) );
            elseif ( ! $Type instanceof SubLineType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();

        return $this;
    }

    /**
     * @return SubLineType
     */
    public function getType()
    {
        return $this->Type;
    }

    /**
     * @param string $Uom
     * @return $this
     */
    public function setUom($Uom)
    {
        $this->Uom = $Uom;
        return $this;
    }

    /**
     * @return string
     */
    public function getUom()
    {
        return $this->Uom;
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
}

