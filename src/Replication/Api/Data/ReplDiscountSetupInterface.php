<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Api\Data;

interface ReplDiscountSetupInterface
{
    /**
     * @param float $AmountToTrigger
     * @return $this
     */
    public function setAmountToTrigger($AmountToTrigger);

    /**
     * @return float
     */
    public function getAmountToTrigger();

    /**
     * @param string $CouponCode
     * @return $this
     */
    public function setCouponCode($CouponCode);

    /**
     * @return string
     */
    public function getCouponCode();

    /**
     * @param float $CouponQtyNeeded
     * @return $this
     */
    public function setCouponQtyNeeded($CouponQtyNeeded);

    /**
     * @return float
     */
    public function getCouponQtyNeeded();

    /**
     * @param string $CurrencyCode
     * @return $this
     */
    public function setCurrencyCode($CurrencyCode);

    /**
     * @return string
     */
    public function getCurrencyCode();

    /**
     * @param string $CustomerDiscountGroup
     * @return $this
     */
    public function setCustomerDiscountGroup($CustomerDiscountGroup);

    /**
     * @return string
     */
    public function getCustomerDiscountGroup();

    /**
     * @param float $DealPriceDiscount
     * @return $this
     */
    public function setDealPriceDiscount($DealPriceDiscount);

    /**
     * @return float
     */
    public function getDealPriceDiscount();

    /**
     * @param float $DealPriceValue
     * @return $this
     */
    public function setDealPriceValue($DealPriceValue);

    /**
     * @return float
     */
    public function getDealPriceValue();

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
     * @param float $DiscountAmountValue
     * @return $this
     */
    public function setDiscountAmountValue($DiscountAmountValue);

    /**
     * @return float
     */
    public function getDiscountAmountValue();

    /**
     * @param float $DiscountValue
     * @return $this
     */
    public function setDiscountValue($DiscountValue);

    /**
     * @return float
     */
    public function getDiscountValue();

    /**
     * @param DiscountValueType $DiscountValueType
     * @return $this
     */
    public function setDiscountValueType($DiscountValueType);

    /**
     * @return DiscountValueType
     */
    public function getDiscountValueType();

    /**
     * @param boolean $Enabled
     * @return $this
     */
    public function setEnabled($Enabled);

    /**
     * @return boolean
     */
    public function getEnabled();

    /**
     * @param boolean $Exclude
     * @return $this
     */
    public function setExclude($Exclude);

    /**
     * @return boolean
     */
    public function getExclude();

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
     * @param boolean $IsPercentage
     * @return $this
     */
    public function setIsPercentage($IsPercentage);

    /**
     * @return boolean
     */
    public function getIsPercentage();

    /**
     * @param float $LineDiscountAmount
     * @return $this
     */
    public function setLineDiscountAmount($LineDiscountAmount);

    /**
     * @return float
     */
    public function getLineDiscountAmount();

    /**
     * @param float $LineDiscountAmountInclVAT
     * @return $this
     */
    public function setLineDiscountAmountInclVAT($LineDiscountAmountInclVAT);

    /**
     * @return float
     */
    public function getLineDiscountAmountInclVAT();

    /**
     * @param boolean $LineDiscountIsAmount
     * @return $this
     */
    public function setLineDiscountIsAmount($LineDiscountIsAmount);

    /**
     * @return boolean
     */
    public function getLineDiscountIsAmount();

    /**
     * @param string $LineGroup
     * @return $this
     */
    public function setLineGroup($LineGroup);

    /**
     * @return string
     */
    public function getLineGroup();

    /**
     * @param float $LineMemberPoints
     * @return $this
     */
    public function setLineMemberPoints($LineMemberPoints);

    /**
     * @return float
     */
    public function getLineMemberPoints();

    /**
     * @param int $LineNumber
     * @return $this
     */
    public function setLineNumber($LineNumber);

    /**
     * @return int
     */
    public function getLineNumber();

    /**
     * @param string $LinePriceGroup
     * @return $this
     */
    public function setLinePriceGroup($LinePriceGroup);

    /**
     * @return string
     */
    public function getLinePriceGroup();

    /**
     * @param ReplDiscountLineType $LineType
     * @return $this
     */
    public function setLineType($LineType);

    /**
     * @return ReplDiscountLineType
     */
    public function getLineType();

    /**
     * @param string $LoyaltySchemeCode
     * @return $this
     */
    public function setLoyaltySchemeCode($LoyaltySchemeCode);

    /**
     * @return string
     */
    public function getLoyaltySchemeCode();

    /**
     * @param float $MaxDiscountAmount
     * @return $this
     */
    public function setMaxDiscountAmount($MaxDiscountAmount);

    /**
     * @return float
     */
    public function getMaxDiscountAmount();

    /**
     * @param string $MemberAttribute
     * @return $this
     */
    public function setMemberAttribute($MemberAttribute);

    /**
     * @return string
     */
    public function getMemberAttribute();

    /**
     * @param float $MemberPoints
     * @return $this
     */
    public function setMemberPoints($MemberPoints);

    /**
     * @return float
     */
    public function getMemberPoints();

    /**
     * @param ReplDiscMemberType $MemberType
     * @return $this
     */
    public function setMemberType($MemberType);

    /**
     * @return ReplDiscMemberType
     */
    public function getMemberType();

    /**
     * @param string $Number
     * @return $this
     */
    public function setNumber($Number);

    /**
     * @return string
     */
    public function getNumber();

    /**
     * @param int $NumberOfItemNeeded
     * @return $this
     */
    public function setNumberOfItemNeeded($NumberOfItemNeeded);

    /**
     * @return int
     */
    public function getNumberOfItemNeeded();

    /**
     * @param string $OfferNo
     * @return $this
     */
    public function setOfferNo($OfferNo);

    /**
     * @return string
     */
    public function getOfferNo();

    /**
     * @param float $OfferPrice
     * @return $this
     */
    public function setOfferPrice($OfferPrice);

    /**
     * @return float
     */
    public function getOfferPrice();

    /**
     * @param float $OfferPriceInclVAT
     * @return $this
     */
    public function setOfferPriceInclVAT($OfferPriceInclVAT);

    /**
     * @return float
     */
    public function getOfferPriceInclVAT();

    /**
     * @param string $PriceGroup
     * @return $this
     */
    public function setPriceGroup($PriceGroup);

    /**
     * @return string
     */
    public function getPriceGroup();

    /**
     * @param int $PriorityNo
     * @return $this
     */
    public function setPriorityNo($PriorityNo);

    /**
     * @return int
     */
    public function getPriorityNo();

    /**
     * @param string $ProductItemCategory
     * @return $this
     */
    public function setProductItemCategory($ProductItemCategory);

    /**
     * @return string
     */
    public function getProductItemCategory();

    /**
     * @param boolean $PromptForAction
     * @return $this
     */
    public function setPromptForAction($PromptForAction);

    /**
     * @return boolean
     */
    public function getPromptForAction();

    /**
     * @param float $SplitDealPriceDiscount
     * @return $this
     */
    public function setSplitDealPriceDiscount($SplitDealPriceDiscount);

    /**
     * @return float
     */
    public function getSplitDealPriceDiscount();

    /**
     * @param float $StandardPrice
     * @return $this
     */
    public function setStandardPrice($StandardPrice);

    /**
     * @return float
     */
    public function getStandardPrice();

    /**
     * @param float $StandardPriceInclVAT
     * @return $this
     */
    public function setStandardPriceInclVAT($StandardPriceInclVAT);

    /**
     * @return float
     */
    public function getStandardPriceInclVAT();

    /**
     * @param float $TenderOffer
     * @return $this
     */
    public function setTenderOffer($TenderOffer);

    /**
     * @return float
     */
    public function getTenderOffer();

    /**
     * @param float $TenderOfferAmount
     * @return $this
     */
    public function setTenderOfferAmount($TenderOfferAmount);

    /**
     * @return float
     */
    public function getTenderOfferAmount();

    /**
     * @param string $TenderTypeCode
     * @return $this
     */
    public function setTenderTypeCode($TenderTypeCode);

    /**
     * @return string
     */
    public function getTenderTypeCode();

    /**
     * @param string $TenderTypeValue
     * @return $this
     */
    public function setTenderTypeValue($TenderTypeValue);

    /**
     * @return string
     */
    public function getTenderTypeValue();

    /**
     * @param boolean $TriggerPopUp
     * @return $this
     */
    public function setTriggerPopUp($TriggerPopUp);

    /**
     * @return boolean
     */
    public function getTriggerPopUp();

    /**
     * @param ReplDiscountType $Type
     * @return $this
     */
    public function setType($Type);

    /**
     * @return ReplDiscountType
     */
    public function getType();

    /**
     * @param string $UnitOfMeasureId
     * @return $this
     */
    public function setUnitOfMeasureId($UnitOfMeasureId);

    /**
     * @return string
     */
    public function getUnitOfMeasureId();

    /**
     * @param string $ValidFromBeforeExpDate
     * @return $this
     */
    public function setValidFromBeforeExpDate($ValidFromBeforeExpDate);

    /**
     * @return string
     */
    public function getValidFromBeforeExpDate();

    /**
     * @param string $ValidToBeforeExpDate
     * @return $this
     */
    public function setValidToBeforeExpDate($ValidToBeforeExpDate);

    /**
     * @return string
     */
    public function getValidToBeforeExpDate();

    /**
     * @param int $ValidationPeriodId
     * @return $this
     */
    public function setValidationPeriodId($ValidationPeriodId);

    /**
     * @return int
     */
    public function getValidationPeriodId();

    /**
     * @param string $VariantId
     * @return $this
     */
    public function setVariantId($VariantId);

    /**
     * @return string
     */
    public function getVariantId();

    /**
     * @param int $VariantType
     * @return $this
     */
    public function setVariantType($VariantType);

    /**
     * @return int
     */
    public function getVariantType();

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope);

    /**
     * @return string
     */
    public function getScope();

    /**
     * @param int $scope_id
     * @return $this
     */
    public function setScopeId($scope_id);

    /**
     * @return int
     */
    public function getScopeId();

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
     * @param string $identity_value
     * @return $this
     */
    public function setIdentityValue($identity_value);

    /**
     * @return string
     */
    public function getIdentityValue();

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

