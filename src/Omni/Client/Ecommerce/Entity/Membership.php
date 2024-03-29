<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class Membership extends Entity
{
    /**
     * @property string $AccessFrom
     */
    protected $AccessFrom = null;

    /**
     * @property string $AccessID
     */
    protected $AccessID = null;

    /**
     * @property string $AccessUntil
     */
    protected $AccessUntil = null;

    /**
     * @property float $Amount
     */
    protected $Amount = null;

    /**
     * @property string $ChargeTo
     */
    protected $ChargeTo = null;

    /**
     * @property string $ChargeToName
     */
    protected $ChargeToName = null;

    /**
     * @property string $CommitmentDate
     */
    protected $CommitmentDate = null;

    /**
     * @property string $ContactNo
     */
    protected $ContactNo = null;

    /**
     * @property string $DateCreated
     */
    protected $DateCreated = null;

    /**
     * @property string $DateExpires
     */
    protected $DateExpires = null;

    /**
     * @property string $DateIssued
     */
    protected $DateIssued = null;

    /**
     * @property string $DateLastProcessed
     */
    protected $DateLastProcessed = null;

    /**
     * @property float $Discount
     */
    protected $Discount = null;

    /**
     * @property string $DiscountReasonCode
     */
    protected $DiscountReasonCode = null;

    /**
     * @property string $EntryType
     */
    protected $EntryType = null;

    /**
     * @property string $LastProcessBatch
     */
    protected $LastProcessBatch = null;

    /**
     * @property string $MemberName
     */
    protected $MemberName = null;

    /**
     * @property string $MembershipDescription
     */
    protected $MembershipDescription = null;

    /**
     * @property string $MembershipType
     */
    protected $MembershipType = null;

    /**
     * @property int $NoOfVisits
     */
    protected $NoOfVisits = null;

    /**
     * @property string $OnHoldUntil
     */
    protected $OnHoldUntil = null;

    /**
     * @property string $PaymentMethodCode
     */
    protected $PaymentMethodCode = null;

    /**
     * @property string $PriceCommitmentExpires
     */
    protected $PriceCommitmentExpires = null;

    /**
     * @property string $SalesPersonCode
     */
    protected $SalesPersonCode = null;

    /**
     * @property string $Status
     */
    protected $Status = null;

    /**
     * @property string $StatusCode
     */
    protected $StatusCode = null;

    /**
     * @property string $StatusDate
     */
    protected $StatusDate = null;

    /**
     * @property float $UnitPrice
     */
    protected $UnitPrice = null;

    /**
     * @param string $AccessFrom
     * @return $this
     */
    public function setAccessFrom($AccessFrom)
    {
        $this->AccessFrom = $AccessFrom;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccessFrom()
    {
        return $this->AccessFrom;
    }

    /**
     * @param string $AccessID
     * @return $this
     */
    public function setAccessID($AccessID)
    {
        $this->AccessID = $AccessID;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccessID()
    {
        return $this->AccessID;
    }

    /**
     * @param string $AccessUntil
     * @return $this
     */
    public function setAccessUntil($AccessUntil)
    {
        $this->AccessUntil = $AccessUntil;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccessUntil()
    {
        return $this->AccessUntil;
    }

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
     * @param string $ChargeTo
     * @return $this
     */
    public function setChargeTo($ChargeTo)
    {
        $this->ChargeTo = $ChargeTo;
        return $this;
    }

    /**
     * @return string
     */
    public function getChargeTo()
    {
        return $this->ChargeTo;
    }

    /**
     * @param string $ChargeToName
     * @return $this
     */
    public function setChargeToName($ChargeToName)
    {
        $this->ChargeToName = $ChargeToName;
        return $this;
    }

    /**
     * @return string
     */
    public function getChargeToName()
    {
        return $this->ChargeToName;
    }

    /**
     * @param string $CommitmentDate
     * @return $this
     */
    public function setCommitmentDate($CommitmentDate)
    {
        $this->CommitmentDate = $CommitmentDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommitmentDate()
    {
        return $this->CommitmentDate;
    }

    /**
     * @param string $ContactNo
     * @return $this
     */
    public function setContactNo($ContactNo)
    {
        $this->ContactNo = $ContactNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getContactNo()
    {
        return $this->ContactNo;
    }

    /**
     * @param string $DateCreated
     * @return $this
     */
    public function setDateCreated($DateCreated)
    {
        $this->DateCreated = $DateCreated;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateCreated()
    {
        return $this->DateCreated;
    }

    /**
     * @param string $DateExpires
     * @return $this
     */
    public function setDateExpires($DateExpires)
    {
        $this->DateExpires = $DateExpires;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateExpires()
    {
        return $this->DateExpires;
    }

    /**
     * @param string $DateIssued
     * @return $this
     */
    public function setDateIssued($DateIssued)
    {
        $this->DateIssued = $DateIssued;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateIssued()
    {
        return $this->DateIssued;
    }

    /**
     * @param string $DateLastProcessed
     * @return $this
     */
    public function setDateLastProcessed($DateLastProcessed)
    {
        $this->DateLastProcessed = $DateLastProcessed;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateLastProcessed()
    {
        return $this->DateLastProcessed;
    }

    /**
     * @param float $Discount
     * @return $this
     */
    public function setDiscount($Discount)
    {
        $this->Discount = $Discount;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscount()
    {
        return $this->Discount;
    }

    /**
     * @param string $DiscountReasonCode
     * @return $this
     */
    public function setDiscountReasonCode($DiscountReasonCode)
    {
        $this->DiscountReasonCode = $DiscountReasonCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getDiscountReasonCode()
    {
        return $this->DiscountReasonCode;
    }

    /**
     * @param string $EntryType
     * @return $this
     */
    public function setEntryType($EntryType)
    {
        $this->EntryType = $EntryType;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntryType()
    {
        return $this->EntryType;
    }

    /**
     * @param string $LastProcessBatch
     * @return $this
     */
    public function setLastProcessBatch($LastProcessBatch)
    {
        $this->LastProcessBatch = $LastProcessBatch;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastProcessBatch()
    {
        return $this->LastProcessBatch;
    }

    /**
     * @param string $MemberName
     * @return $this
     */
    public function setMemberName($MemberName)
    {
        $this->MemberName = $MemberName;
        return $this;
    }

    /**
     * @return string
     */
    public function getMemberName()
    {
        return $this->MemberName;
    }

    /**
     * @param string $MembershipDescription
     * @return $this
     */
    public function setMembershipDescription($MembershipDescription)
    {
        $this->MembershipDescription = $MembershipDescription;
        return $this;
    }

    /**
     * @return string
     */
    public function getMembershipDescription()
    {
        return $this->MembershipDescription;
    }

    /**
     * @param string $MembershipType
     * @return $this
     */
    public function setMembershipType($MembershipType)
    {
        $this->MembershipType = $MembershipType;
        return $this;
    }

    /**
     * @return string
     */
    public function getMembershipType()
    {
        return $this->MembershipType;
    }

    /**
     * @param int $NoOfVisits
     * @return $this
     */
    public function setNoOfVisits($NoOfVisits)
    {
        $this->NoOfVisits = $NoOfVisits;
        return $this;
    }

    /**
     * @return int
     */
    public function getNoOfVisits()
    {
        return $this->NoOfVisits;
    }

    /**
     * @param string $OnHoldUntil
     * @return $this
     */
    public function setOnHoldUntil($OnHoldUntil)
    {
        $this->OnHoldUntil = $OnHoldUntil;
        return $this;
    }

    /**
     * @return string
     */
    public function getOnHoldUntil()
    {
        return $this->OnHoldUntil;
    }

    /**
     * @param string $PaymentMethodCode
     * @return $this
     */
    public function setPaymentMethodCode($PaymentMethodCode)
    {
        $this->PaymentMethodCode = $PaymentMethodCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return $this->PaymentMethodCode;
    }

    /**
     * @param string $PriceCommitmentExpires
     * @return $this
     */
    public function setPriceCommitmentExpires($PriceCommitmentExpires)
    {
        $this->PriceCommitmentExpires = $PriceCommitmentExpires;
        return $this;
    }

    /**
     * @return string
     */
    public function getPriceCommitmentExpires()
    {
        return $this->PriceCommitmentExpires;
    }

    /**
     * @param string $SalesPersonCode
     * @return $this
     */
    public function setSalesPersonCode($SalesPersonCode)
    {
        $this->SalesPersonCode = $SalesPersonCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalesPersonCode()
    {
        return $this->SalesPersonCode;
    }

    /**
     * @param string $Status
     * @return $this
     */
    public function setStatus($Status)
    {
        $this->Status = $Status;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * @param string $StatusCode
     * @return $this
     */
    public function setStatusCode($StatusCode)
    {
        $this->StatusCode = $StatusCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatusCode()
    {
        return $this->StatusCode;
    }

    /**
     * @param string $StatusDate
     * @return $this
     */
    public function setStatusDate($StatusDate)
    {
        $this->StatusDate = $StatusDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatusDate()
    {
        return $this->StatusDate;
    }

    /**
     * @param float $UnitPrice
     * @return $this
     */
    public function setUnitPrice($UnitPrice)
    {
        $this->UnitPrice = $UnitPrice;
        return $this;
    }

    /**
     * @return float
     */
    public function getUnitPrice()
    {
        return $this->UnitPrice;
    }
}

