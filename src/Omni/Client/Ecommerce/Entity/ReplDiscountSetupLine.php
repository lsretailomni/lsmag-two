<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountLineType;
use Ls\Omni\Exception\InvalidEnumException;

class ReplDiscountSetupLine
{
    /**
     * @property string $CurrencyCode
     */
    protected $CurrencyCode = null;

    /**
     * @property float $DealPriceDiscount
     */
    protected $DealPriceDiscount = null;

    /**
     * @property float $DiscountAmount
     */
    protected $DiscountAmount = null;

    /**
     * @property float $DiscountAmountInclVAT
     */
    protected $DiscountAmountInclVAT = null;

    /**
     * @property boolean $DiscountIsAmount
     */
    protected $DiscountIsAmount = null;

    /**
     * @property boolean $Exclude
     */
    protected $Exclude = null;

    /**
     * @property string $LineGroup
     */
    protected $LineGroup = null;

    /**
     * @property int $LineNumber
     */
    protected $LineNumber = null;

    /**
     * @property float $MemberPoints
     */
    protected $MemberPoints = null;

    /**
     * @property string $Number
     */
    protected $Number = null;

    /**
     * @property int $NumberOfItemNeeded
     */
    protected $NumberOfItemNeeded = null;

    /**
     * @property float $OfferPrice
     */
    protected $OfferPrice = null;

    /**
     * @property float $OfferPriceInclVAT
     */
    protected $OfferPriceInclVAT = null;

    /**
     * @property string $PriceGroup
     */
    protected $PriceGroup = null;

    /**
     * @property string $ProductGroupCategory
     */
    protected $ProductGroupCategory = null;

    /**
     * @property float $SplitDealPriceDiscount
     */
    protected $SplitDealPriceDiscount = null;

    /**
     * @property float $StandardPrice
     */
    protected $StandardPrice = null;

    /**
     * @property float $StandardPriceInclVAT
     */
    protected $StandardPriceInclVAT = null;

    /**
     * @property boolean $TriggerPopUp
     */
    protected $TriggerPopUp = null;

    /**
     * @property ReplDiscountLineType $Type
     */
    protected $Type = null;

    /**
     * @property string $UnitOfMeasureId
     */
    protected $UnitOfMeasureId = null;

    /**
     * @property string $ValidFromBeforeExpDate
     */
    protected $ValidFromBeforeExpDate = null;

    /**
     * @property string $ValidToBeforeExpDate
     */
    protected $ValidToBeforeExpDate = null;

    /**
     * @property string $VariantId
     */
    protected $VariantId = null;

    /**
     * @property int $VariantType
     */
    protected $VariantType = null;

    /**
     * @property string $scope
     */
    protected $scope = null;

    /**
     * @property int $scope_id
     */
    protected $scope_id = null;

    /**
     * @param string $CurrencyCode
     * @return $this
     */
    public function setCurrencyCode($CurrencyCode)
    {
        $this->CurrencyCode = $CurrencyCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->CurrencyCode;
    }

    /**
     * @param float $DealPriceDiscount
     * @return $this
     */
    public function setDealPriceDiscount($DealPriceDiscount)
    {
        $this->DealPriceDiscount = $DealPriceDiscount;
        return $this;
    }

    /**
     * @return float
     */
    public function getDealPriceDiscount()
    {
        return $this->DealPriceDiscount;
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
     * @param float $DiscountAmountInclVAT
     * @return $this
     */
    public function setDiscountAmountInclVAT($DiscountAmountInclVAT)
    {
        $this->DiscountAmountInclVAT = $DiscountAmountInclVAT;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmountInclVAT()
    {
        return $this->DiscountAmountInclVAT;
    }

    /**
     * @param boolean $DiscountIsAmount
     * @return $this
     */
    public function setDiscountIsAmount($DiscountIsAmount)
    {
        $this->DiscountIsAmount = $DiscountIsAmount;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDiscountIsAmount()
    {
        return $this->DiscountIsAmount;
    }

    /**
     * @param boolean $Exclude
     * @return $this
     */
    public function setExclude($Exclude)
    {
        $this->Exclude = $Exclude;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getExclude()
    {
        return $this->Exclude;
    }

    /**
     * @param string $LineGroup
     * @return $this
     */
    public function setLineGroup($LineGroup)
    {
        $this->LineGroup = $LineGroup;
        return $this;
    }

    /**
     * @return string
     */
    public function getLineGroup()
    {
        return $this->LineGroup;
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
     * @param float $MemberPoints
     * @return $this
     */
    public function setMemberPoints($MemberPoints)
    {
        $this->MemberPoints = $MemberPoints;
        return $this;
    }

    /**
     * @return float
     */
    public function getMemberPoints()
    {
        return $this->MemberPoints;
    }

    /**
     * @param string $Number
     * @return $this
     */
    public function setNumber($Number)
    {
        $this->Number = $Number;
        return $this;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->Number;
    }

    /**
     * @param int $NumberOfItemNeeded
     * @return $this
     */
    public function setNumberOfItemNeeded($NumberOfItemNeeded)
    {
        $this->NumberOfItemNeeded = $NumberOfItemNeeded;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfItemNeeded()
    {
        return $this->NumberOfItemNeeded;
    }

    /**
     * @param float $OfferPrice
     * @return $this
     */
    public function setOfferPrice($OfferPrice)
    {
        $this->OfferPrice = $OfferPrice;
        return $this;
    }

    /**
     * @return float
     */
    public function getOfferPrice()
    {
        return $this->OfferPrice;
    }

    /**
     * @param float $OfferPriceInclVAT
     * @return $this
     */
    public function setOfferPriceInclVAT($OfferPriceInclVAT)
    {
        $this->OfferPriceInclVAT = $OfferPriceInclVAT;
        return $this;
    }

    /**
     * @return float
     */
    public function getOfferPriceInclVAT()
    {
        return $this->OfferPriceInclVAT;
    }

    /**
     * @param string $PriceGroup
     * @return $this
     */
    public function setPriceGroup($PriceGroup)
    {
        $this->PriceGroup = $PriceGroup;
        return $this;
    }

    /**
     * @return string
     */
    public function getPriceGroup()
    {
        return $this->PriceGroup;
    }

    /**
     * @param string $ProductGroupCategory
     * @return $this
     */
    public function setProductGroupCategory($ProductGroupCategory)
    {
        $this->ProductGroupCategory = $ProductGroupCategory;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductGroupCategory()
    {
        return $this->ProductGroupCategory;
    }

    /**
     * @param float $SplitDealPriceDiscount
     * @return $this
     */
    public function setSplitDealPriceDiscount($SplitDealPriceDiscount)
    {
        $this->SplitDealPriceDiscount = $SplitDealPriceDiscount;
        return $this;
    }

    /**
     * @return float
     */
    public function getSplitDealPriceDiscount()
    {
        return $this->SplitDealPriceDiscount;
    }

    /**
     * @param float $StandardPrice
     * @return $this
     */
    public function setStandardPrice($StandardPrice)
    {
        $this->StandardPrice = $StandardPrice;
        return $this;
    }

    /**
     * @return float
     */
    public function getStandardPrice()
    {
        return $this->StandardPrice;
    }

    /**
     * @param float $StandardPriceInclVAT
     * @return $this
     */
    public function setStandardPriceInclVAT($StandardPriceInclVAT)
    {
        $this->StandardPriceInclVAT = $StandardPriceInclVAT;
        return $this;
    }

    /**
     * @return float
     */
    public function getStandardPriceInclVAT()
    {
        return $this->StandardPriceInclVAT;
    }

    /**
     * @param boolean $TriggerPopUp
     * @return $this
     */
    public function setTriggerPopUp($TriggerPopUp)
    {
        $this->TriggerPopUp = $TriggerPopUp;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getTriggerPopUp()
    {
        return $this->TriggerPopUp;
    }

    /**
     * @param ReplDiscountLineType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof ReplDiscountLineType ) {
            if ( ReplDiscountLineType::isValid( $Type ) )
                $Type = new ReplDiscountLineType( $Type );
            elseif ( ReplDiscountLineType::isValidKey( $Type ) )
                $Type = new ReplDiscountLineType( constant( "ReplDiscountLineType::$Type" ) );
            elseif ( ! $Type instanceof ReplDiscountLineType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();

        return $this;
    }

    /**
     * @return ReplDiscountLineType
     */
    public function getType()
    {
        return $this->Type;
    }

    /**
     * @param string $UnitOfMeasureId
     * @return $this
     */
    public function setUnitOfMeasureId($UnitOfMeasureId)
    {
        $this->UnitOfMeasureId = $UnitOfMeasureId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnitOfMeasureId()
    {
        return $this->UnitOfMeasureId;
    }

    /**
     * @param string $ValidFromBeforeExpDate
     * @return $this
     */
    public function setValidFromBeforeExpDate($ValidFromBeforeExpDate)
    {
        $this->ValidFromBeforeExpDate = $ValidFromBeforeExpDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getValidFromBeforeExpDate()
    {
        return $this->ValidFromBeforeExpDate;
    }

    /**
     * @param string $ValidToBeforeExpDate
     * @return $this
     */
    public function setValidToBeforeExpDate($ValidToBeforeExpDate)
    {
        $this->ValidToBeforeExpDate = $ValidToBeforeExpDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getValidToBeforeExpDate()
    {
        return $this->ValidToBeforeExpDate;
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
     * @param int $VariantType
     * @return $this
     */
    public function setVariantType($VariantType)
    {
        $this->VariantType = $VariantType;
        return $this;
    }

    /**
     * @return int
     */
    public function getVariantType()
    {
        return $this->VariantType;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param int $scope_id
     * @return $this
     */
    public function setScopeId($scope_id)
    {
        $this->scope_id = $scope_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        return $this->scope_id;
    }
}

