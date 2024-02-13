<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplDiscountSetupInterface;

class ReplDiscountSetup extends AbstractModel implements ReplDiscountSetupInterface, IdentityInterface
{
    public const CACHE_TAG = 'ls_replication_repl_discount_setup';

    protected $_cacheTag = 'ls_replication_repl_discount_setup';

    protected $_eventPrefix = 'ls_replication_repl_discount_setup';

    /**
     * @property ArrayOfReplDiscountSetupLine $Lines
     */
    protected $Lines = null;

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
     * @property string $CustomerDiscountGroup
     */
    protected $CustomerDiscountGroup = null;

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
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

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
     * @property string $OfferNo
     */
    protected $OfferNo = null;

    /**
     * @property string $PriceGroup
     */
    protected $PriceGroup = null;

    /**
     * @property int $PriorityNo
     */
    protected $PriorityNo = null;

    /**
     * @property boolean $PromptForAction
     */
    protected $PromptForAction = null;

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
     * @property ReplDiscountType $Type
     */
    protected $Type = null;

    /**
     * @property int $ValidationPeriodId
     */
    protected $ValidationPeriodId = null;

    /**
     * @property string $scope
     */
    protected $scope = null;

    /**
     * @property int $scope_id
     */
    protected $scope_id = null;

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
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplDiscountSetup' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @param ArrayOfReplDiscountSetupLine $Lines
     * @return $this
     */
    public function setLines($Lines)
    {
        $this->setData( 'Lines', $Lines );
        $this->Lines = $Lines;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfReplDiscountSetupLine
     */
    public function getLines()
    {
        return $this->getData( 'Lines' );
    }

    /**
     * @param float $AmountToTrigger
     * @return $this
     */
    public function setAmountToTrigger($AmountToTrigger)
    {
        $this->setData( 'AmountToTrigger', $AmountToTrigger );
        $this->AmountToTrigger = $AmountToTrigger;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountToTrigger()
    {
        return $this->getData( 'AmountToTrigger' );
    }

    /**
     * @param string $CouponCode
     * @return $this
     */
    public function setCouponCode($CouponCode)
    {
        $this->setData( 'CouponCode', $CouponCode );
        $this->CouponCode = $CouponCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCouponCode()
    {
        return $this->getData( 'CouponCode' );
    }

    /**
     * @param float $CouponQtyNeeded
     * @return $this
     */
    public function setCouponQtyNeeded($CouponQtyNeeded)
    {
        $this->setData( 'CouponQtyNeeded', $CouponQtyNeeded );
        $this->CouponQtyNeeded = $CouponQtyNeeded;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getCouponQtyNeeded()
    {
        return $this->getData( 'CouponQtyNeeded' );
    }

    /**
     * @param string $CustomerDiscountGroup
     * @return $this
     */
    public function setCustomerDiscountGroup($CustomerDiscountGroup)
    {
        $this->setData( 'CustomerDiscountGroup', $CustomerDiscountGroup );
        $this->CustomerDiscountGroup = $CustomerDiscountGroup;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerDiscountGroup()
    {
        return $this->getData( 'CustomerDiscountGroup' );
    }

    /**
     * @param float $DealPriceValue
     * @return $this
     */
    public function setDealPriceValue($DealPriceValue)
    {
        $this->setData( 'DealPriceValue', $DealPriceValue );
        $this->DealPriceValue = $DealPriceValue;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getDealPriceValue()
    {
        return $this->getData( 'DealPriceValue' );
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
     * @param float $DiscountAmountValue
     * @return $this
     */
    public function setDiscountAmountValue($DiscountAmountValue)
    {
        $this->setData( 'DiscountAmountValue', $DiscountAmountValue );
        $this->DiscountAmountValue = $DiscountAmountValue;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmountValue()
    {
        return $this->getData( 'DiscountAmountValue' );
    }

    /**
     * @param float $DiscountValue
     * @return $this
     */
    public function setDiscountValue($DiscountValue)
    {
        $this->setData( 'DiscountValue', $DiscountValue );
        $this->DiscountValue = $DiscountValue;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountValue()
    {
        return $this->getData( 'DiscountValue' );
    }

    /**
     * @param DiscountValueType $DiscountValueType
     * @return $this
     */
    public function setDiscountValueType($DiscountValueType)
    {
        $this->setData( 'DiscountValueType', $DiscountValueType );
        $this->DiscountValueType = $DiscountValueType;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return DiscountValueType
     */
    public function getDiscountValueType()
    {
        return $this->getData( 'DiscountValueType' );
    }

    /**
     * @param boolean $Enabled
     * @return $this
     */
    public function setEnabled($Enabled)
    {
        $this->setData( 'Enabled', $Enabled );
        $this->Enabled = $Enabled;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->getData( 'Enabled' );
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
     * @param string $LoyaltySchemeCode
     * @return $this
     */
    public function setLoyaltySchemeCode($LoyaltySchemeCode)
    {
        $this->setData( 'LoyaltySchemeCode', $LoyaltySchemeCode );
        $this->LoyaltySchemeCode = $LoyaltySchemeCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getLoyaltySchemeCode()
    {
        return $this->getData( 'LoyaltySchemeCode' );
    }

    /**
     * @param float $MaxDiscountAmount
     * @return $this
     */
    public function setMaxDiscountAmount($MaxDiscountAmount)
    {
        $this->setData( 'MaxDiscountAmount', $MaxDiscountAmount );
        $this->MaxDiscountAmount = $MaxDiscountAmount;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxDiscountAmount()
    {
        return $this->getData( 'MaxDiscountAmount' );
    }

    /**
     * @param string $MemberAttribute
     * @return $this
     */
    public function setMemberAttribute($MemberAttribute)
    {
        $this->setData( 'MemberAttribute', $MemberAttribute );
        $this->MemberAttribute = $MemberAttribute;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getMemberAttribute()
    {
        return $this->getData( 'MemberAttribute' );
    }

    /**
     * @param float $MemberPoints
     * @return $this
     */
    public function setMemberPoints($MemberPoints)
    {
        $this->setData( 'MemberPoints', $MemberPoints );
        $this->MemberPoints = $MemberPoints;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getMemberPoints()
    {
        return $this->getData( 'MemberPoints' );
    }

    /**
     * @param ReplDiscMemberType $MemberType
     * @return $this
     */
    public function setMemberType($MemberType)
    {
        $this->setData( 'MemberType', $MemberType );
        $this->MemberType = $MemberType;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ReplDiscMemberType
     */
    public function getMemberType()
    {
        return $this->getData( 'MemberType' );
    }

    /**
     * @param string $OfferNo
     * @return $this
     */
    public function setOfferNo($OfferNo)
    {
        $this->setData( 'OfferNo', $OfferNo );
        $this->OfferNo = $OfferNo;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getOfferNo()
    {
        return $this->getData( 'OfferNo' );
    }

    /**
     * @param string $PriceGroup
     * @return $this
     */
    public function setPriceGroup($PriceGroup)
    {
        $this->setData( 'PriceGroup', $PriceGroup );
        $this->PriceGroup = $PriceGroup;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getPriceGroup()
    {
        return $this->getData( 'PriceGroup' );
    }

    /**
     * @param int $PriorityNo
     * @return $this
     */
    public function setPriorityNo($PriorityNo)
    {
        $this->setData( 'PriorityNo', $PriorityNo );
        $this->PriorityNo = $PriorityNo;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getPriorityNo()
    {
        return $this->getData( 'PriorityNo' );
    }

    /**
     * @param boolean $PromptForAction
     * @return $this
     */
    public function setPromptForAction($PromptForAction)
    {
        $this->setData( 'PromptForAction', $PromptForAction );
        $this->PromptForAction = $PromptForAction;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getPromptForAction()
    {
        return $this->getData( 'PromptForAction' );
    }

    /**
     * @param float $TenderOffer
     * @return $this
     */
    public function setTenderOffer($TenderOffer)
    {
        $this->setData( 'TenderOffer', $TenderOffer );
        $this->TenderOffer = $TenderOffer;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getTenderOffer()
    {
        return $this->getData( 'TenderOffer' );
    }

    /**
     * @param float $TenderOfferAmount
     * @return $this
     */
    public function setTenderOfferAmount($TenderOfferAmount)
    {
        $this->setData( 'TenderOfferAmount', $TenderOfferAmount );
        $this->TenderOfferAmount = $TenderOfferAmount;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getTenderOfferAmount()
    {
        return $this->getData( 'TenderOfferAmount' );
    }

    /**
     * @param string $TenderTypeCode
     * @return $this
     */
    public function setTenderTypeCode($TenderTypeCode)
    {
        $this->setData( 'TenderTypeCode', $TenderTypeCode );
        $this->TenderTypeCode = $TenderTypeCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getTenderTypeCode()
    {
        return $this->getData( 'TenderTypeCode' );
    }

    /**
     * @param string $TenderTypeValue
     * @return $this
     */
    public function setTenderTypeValue($TenderTypeValue)
    {
        $this->setData( 'TenderTypeValue', $TenderTypeValue );
        $this->TenderTypeValue = $TenderTypeValue;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getTenderTypeValue()
    {
        return $this->getData( 'TenderTypeValue' );
    }

    /**
     * @param ReplDiscountType $Type
     * @return $this
     */
    public function setType($Type)
    {
        $this->setData( 'Type', $Type );
        $this->Type = $Type;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ReplDiscountType
     */
    public function getType()
    {
        return $this->getData( 'Type' );
    }

    /**
     * @param int $ValidationPeriodId
     * @return $this
     */
    public function setValidationPeriodId($ValidationPeriodId)
    {
        $this->setData( 'ValidationPeriodId', $ValidationPeriodId );
        $this->ValidationPeriodId = $ValidationPeriodId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getValidationPeriodId()
    {
        return $this->getData( 'ValidationPeriodId' );
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->setData( 'scope', $scope );
        $this->scope = $scope;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->getData( 'scope' );
    }

    /**
     * @param int $scope_id
     * @return $this
     */
    public function setScopeId($scope_id)
    {
        $this->setData( 'scope_id', $scope_id );
        $this->scope_id = $scope_id;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        return $this->getData( 'scope_id' );
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

