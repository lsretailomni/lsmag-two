<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountValueType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountLineType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscMemberType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountType;
use Ls\Omni\Exception\InvalidEnumException;

class ReplDiscountSetup
{
    /**
     * @property float $AmountToTrigger
     */
    protected $AmountToTrigger = null;

    /**
     * @property string $CouponCode
     */
    protected $CouponCode = null;

    /**
     * @property float $CouponQtyNeeded
     */
    protected $CouponQtyNeeded = null;

    /**
     * @property string $CurrencyCode
     */
    protected $CurrencyCode = null;

    /**
     * @property string $CustomerDiscountGroup
     */
    protected $CustomerDiscountGroup = null;

    /**
     * @property float $DealPriceDiscount
     */
    protected $DealPriceDiscount = null;

    /**
     * @property float $DealPriceValue
     */
    protected $DealPriceValue = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $Details
     */
    protected $Details = null;

    /**
     * @property float $DiscountAmountValue
     */
    protected $DiscountAmountValue = null;

    /**
     * @property float $DiscountValue
     */
    protected $DiscountValue = null;

    /**
     * @property DiscountValueType $DiscountValueType
     */
    protected $DiscountValueType = null;

    /**
     * @property boolean $Enabled
     */
    protected $Enabled = null;

    /**
     * @property boolean $Exclude
     */
    protected $Exclude = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property boolean $IsPercentage
     */
    protected $IsPercentage = null;

    /**
     * @property float $LineDiscountAmount
     */
    protected $LineDiscountAmount = null;

    /**
     * @property float $LineDiscountAmountInclVAT
     */
    protected $LineDiscountAmountInclVAT = null;

    /**
     * @property string $LineGroup
     */
    protected $LineGroup = null;

    /**
     * @property float $LineMemberPoints
     */
    protected $LineMemberPoints = null;

    /**
     * @property int $LineNumber
     */
    protected $LineNumber = null;

    /**
     * @property string $LinePriceGroup
     */
    protected $LinePriceGroup = null;

    /**
     * @property ReplDiscountLineType $LineType
     */
    protected $LineType = null;

    /**
     * @property string $LoyaltySchemeCode
     */
    protected $LoyaltySchemeCode = null;

    /**
     * @property float $MaxDiscountAmount
     */
    protected $MaxDiscountAmount = null;

    /**
     * @property string $MemberAttribute
     */
    protected $MemberAttribute = null;

    /**
     * @property float $MemberPoints
     */
    protected $MemberPoints = null;

    /**
     * @property ReplDiscMemberType $MemberType
     */
    protected $MemberType = null;

    /**
     * @property string $Number
     */
    protected $Number = null;

    /**
     * @property int $NumberOfItemNeeded
     */
    protected $NumberOfItemNeeded = null;

    /**
     * @property string $OfferNo
     */
    protected $OfferNo = null;

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
     * @property int $PriorityNo
     */
    protected $PriorityNo = null;

    /**
     * @property string $ProductItemCategory
     */
    protected $ProductItemCategory = null;

    /**
     * @property boolean $PromptForAction
     */
    protected $PromptForAction = null;

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
     * @property float $TenderOffer
     */
    protected $TenderOffer = null;

    /**
     * @property float $TenderOfferAmount
     */
    protected $TenderOfferAmount = null;

    /**
     * @property string $TenderTypeCode
     */
    protected $TenderTypeCode = null;

    /**
     * @property string $TenderTypeValue
     */
    protected $TenderTypeValue = null;

    /**
     * @property boolean $TriggerPopUp
     */
    protected $TriggerPopUp = null;

    /**
     * @property ReplDiscountType $Type
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
     * @property int $ValidationPeriodId
     */
    protected $ValidationPeriodId = null;

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
     * @param float $AmountToTrigger
     * @return $this
     */
    public function setAmountToTrigger($AmountToTrigger)
    {
        $this->AmountToTrigger = $AmountToTrigger;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountToTrigger()
    {
        return $this->AmountToTrigger;
    }

    /**
     * @param string $CouponCode
     * @return $this
     */
    public function setCouponCode($CouponCode)
    {
        $this->CouponCode = $CouponCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCouponCode()
    {
        return $this->CouponCode;
    }

    /**
     * @param float $CouponQtyNeeded
     * @return $this
     */
    public function setCouponQtyNeeded($CouponQtyNeeded)
    {
        $this->CouponQtyNeeded = $CouponQtyNeeded;
        return $this;
    }

    /**
     * @return float
     */
    public function getCouponQtyNeeded()
    {
        return $this->CouponQtyNeeded;
    }

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
     * @param string $CustomerDiscountGroup
     * @return $this
     */
    public function setCustomerDiscountGroup($CustomerDiscountGroup)
    {
        $this->CustomerDiscountGroup = $CustomerDiscountGroup;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerDiscountGroup()
    {
        return $this->CustomerDiscountGroup;
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
     * @param float $DealPriceValue
     * @return $this
     */
    public function setDealPriceValue($DealPriceValue)
    {
        $this->DealPriceValue = $DealPriceValue;
        return $this;
    }

    /**
     * @return float
     */
    public function getDealPriceValue()
    {
        return $this->DealPriceValue;
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
     * @param string $Details
     * @return $this
     */
    public function setDetails($Details)
    {
        $this->Details = $Details;
        return $this;
    }

    /**
     * @return string
     */
    public function getDetails()
    {
        return $this->Details;
    }

    /**
     * @param float $DiscountAmountValue
     * @return $this
     */
    public function setDiscountAmountValue($DiscountAmountValue)
    {
        $this->DiscountAmountValue = $DiscountAmountValue;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmountValue()
    {
        return $this->DiscountAmountValue;
    }

    /**
     * @param float $DiscountValue
     * @return $this
     */
    public function setDiscountValue($DiscountValue)
    {
        $this->DiscountValue = $DiscountValue;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountValue()
    {
        return $this->DiscountValue;
    }

    /**
     * @param DiscountValueType|string $DiscountValueType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setDiscountValueType($DiscountValueType)
    {
        if ( ! $DiscountValueType instanceof DiscountValueType ) {
            if ( DiscountValueType::isValid( $DiscountValueType ) )
                $DiscountValueType = new DiscountValueType( $DiscountValueType );
            elseif ( DiscountValueType::isValidKey( $DiscountValueType ) )
                $DiscountValueType = new DiscountValueType( constant( "DiscountValueType::$DiscountValueType" ) );
            elseif ( ! $DiscountValueType instanceof DiscountValueType )
                throw new InvalidEnumException();
        }
        $this->DiscountValueType = $DiscountValueType->getValue();

        return $this;
    }

    /**
     * @return DiscountValueType
     */
    public function getDiscountValueType()
    {
        return $this->DiscountValueType;
    }

    /**
     * @param boolean $Enabled
     * @return $this
     */
    public function setEnabled($Enabled)
    {
        $this->Enabled = $Enabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->Enabled;
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
     * @param boolean $IsDeleted
     * @return $this
     */
    public function setIsDeleted($IsDeleted)
    {
        $this->IsDeleted = $IsDeleted;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->IsDeleted;
    }

    /**
     * @param boolean $IsPercentage
     * @return $this
     */
    public function setIsPercentage($IsPercentage)
    {
        $this->IsPercentage = $IsPercentage;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsPercentage()
    {
        return $this->IsPercentage;
    }

    /**
     * @param float $LineDiscountAmount
     * @return $this
     */
    public function setLineDiscountAmount($LineDiscountAmount)
    {
        $this->LineDiscountAmount = $LineDiscountAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getLineDiscountAmount()
    {
        return $this->LineDiscountAmount;
    }

    /**
     * @param float $LineDiscountAmountInclVAT
     * @return $this
     */
    public function setLineDiscountAmountInclVAT($LineDiscountAmountInclVAT)
    {
        $this->LineDiscountAmountInclVAT = $LineDiscountAmountInclVAT;
        return $this;
    }

    /**
     * @return float
     */
    public function getLineDiscountAmountInclVAT()
    {
        return $this->LineDiscountAmountInclVAT;
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
     * @param float $LineMemberPoints
     * @return $this
     */
    public function setLineMemberPoints($LineMemberPoints)
    {
        $this->LineMemberPoints = $LineMemberPoints;
        return $this;
    }

    /**
     * @return float
     */
    public function getLineMemberPoints()
    {
        return $this->LineMemberPoints;
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
     * @param string $LinePriceGroup
     * @return $this
     */
    public function setLinePriceGroup($LinePriceGroup)
    {
        $this->LinePriceGroup = $LinePriceGroup;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinePriceGroup()
    {
        return $this->LinePriceGroup;
    }

    /**
     * @param ReplDiscountLineType|string $LineType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setLineType($LineType)
    {
        if ( ! $LineType instanceof ReplDiscountLineType ) {
            if ( ReplDiscountLineType::isValid( $LineType ) )
                $LineType = new ReplDiscountLineType( $LineType );
            elseif ( ReplDiscountLineType::isValidKey( $LineType ) )
                $LineType = new ReplDiscountLineType( constant( "ReplDiscountLineType::$LineType" ) );
            elseif ( ! $LineType instanceof ReplDiscountLineType )
                throw new InvalidEnumException();
        }
        $this->LineType = $LineType->getValue();

        return $this;
    }

    /**
     * @return ReplDiscountLineType
     */
    public function getLineType()
    {
        return $this->LineType;
    }

    /**
     * @param string $LoyaltySchemeCode
     * @return $this
     */
    public function setLoyaltySchemeCode($LoyaltySchemeCode)
    {
        $this->LoyaltySchemeCode = $LoyaltySchemeCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoyaltySchemeCode()
    {
        return $this->LoyaltySchemeCode;
    }

    /**
     * @param float $MaxDiscountAmount
     * @return $this
     */
    public function setMaxDiscountAmount($MaxDiscountAmount)
    {
        $this->MaxDiscountAmount = $MaxDiscountAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxDiscountAmount()
    {
        return $this->MaxDiscountAmount;
    }

    /**
     * @param string $MemberAttribute
     * @return $this
     */
    public function setMemberAttribute($MemberAttribute)
    {
        $this->MemberAttribute = $MemberAttribute;
        return $this;
    }

    /**
     * @return string
     */
    public function getMemberAttribute()
    {
        return $this->MemberAttribute;
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
     * @param ReplDiscMemberType|string $MemberType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setMemberType($MemberType)
    {
        if ( ! $MemberType instanceof ReplDiscMemberType ) {
            if ( ReplDiscMemberType::isValid( $MemberType ) )
                $MemberType = new ReplDiscMemberType( $MemberType );
            elseif ( ReplDiscMemberType::isValidKey( $MemberType ) )
                $MemberType = new ReplDiscMemberType( constant( "ReplDiscMemberType::$MemberType" ) );
            elseif ( ! $MemberType instanceof ReplDiscMemberType )
                throw new InvalidEnumException();
        }
        $this->MemberType = $MemberType->getValue();

        return $this;
    }

    /**
     * @return ReplDiscMemberType
     */
    public function getMemberType()
    {
        return $this->MemberType;
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
     * @param string $OfferNo
     * @return $this
     */
    public function setOfferNo($OfferNo)
    {
        $this->OfferNo = $OfferNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getOfferNo()
    {
        return $this->OfferNo;
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
     * @param int $PriorityNo
     * @return $this
     */
    public function setPriorityNo($PriorityNo)
    {
        $this->PriorityNo = $PriorityNo;
        return $this;
    }

    /**
     * @return int
     */
    public function getPriorityNo()
    {
        return $this->PriorityNo;
    }

    /**
     * @param string $ProductItemCategory
     * @return $this
     */
    public function setProductItemCategory($ProductItemCategory)
    {
        $this->ProductItemCategory = $ProductItemCategory;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductItemCategory()
    {
        return $this->ProductItemCategory;
    }

    /**
     * @param boolean $PromptForAction
     * @return $this
     */
    public function setPromptForAction($PromptForAction)
    {
        $this->PromptForAction = $PromptForAction;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getPromptForAction()
    {
        return $this->PromptForAction;
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
     * @param float $TenderOffer
     * @return $this
     */
    public function setTenderOffer($TenderOffer)
    {
        $this->TenderOffer = $TenderOffer;
        return $this;
    }

    /**
     * @return float
     */
    public function getTenderOffer()
    {
        return $this->TenderOffer;
    }

    /**
     * @param float $TenderOfferAmount
     * @return $this
     */
    public function setTenderOfferAmount($TenderOfferAmount)
    {
        $this->TenderOfferAmount = $TenderOfferAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getTenderOfferAmount()
    {
        return $this->TenderOfferAmount;
    }

    /**
     * @param string $TenderTypeCode
     * @return $this
     */
    public function setTenderTypeCode($TenderTypeCode)
    {
        $this->TenderTypeCode = $TenderTypeCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getTenderTypeCode()
    {
        return $this->TenderTypeCode;
    }

    /**
     * @param string $TenderTypeValue
     * @return $this
     */
    public function setTenderTypeValue($TenderTypeValue)
    {
        $this->TenderTypeValue = $TenderTypeValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getTenderTypeValue()
    {
        return $this->TenderTypeValue;
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
     * @param ReplDiscountType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof ReplDiscountType ) {
            if ( ReplDiscountType::isValid( $Type ) )
                $Type = new ReplDiscountType( $Type );
            elseif ( ReplDiscountType::isValidKey( $Type ) )
                $Type = new ReplDiscountType( constant( "ReplDiscountType::$Type" ) );
            elseif ( ! $Type instanceof ReplDiscountType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();

        return $this;
    }

    /**
     * @return ReplDiscountType
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
     * @param int $ValidationPeriodId
     * @return $this
     */
    public function setValidationPeriodId($ValidationPeriodId)
    {
        $this->ValidationPeriodId = $ValidationPeriodId;
        return $this;
    }

    /**
     * @return int
     */
    public function getValidationPeriodId()
    {
        return $this->ValidationPeriodId;
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

