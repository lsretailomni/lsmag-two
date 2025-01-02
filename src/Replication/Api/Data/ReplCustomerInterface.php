<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Api\Data;

interface ReplCustomerInterface
{
    /**
     * @param ArrayOfCard $Cards
     * @return $this
     */
    public function setCards($Cards);

    /**
     * @return ArrayOfCard
     */
    public function getCards();

    /**
     * @param string $AccountNumber
     * @return $this
     */
    public function setAccountNumber($AccountNumber);

    /**
     * @return string
     */
    public function getAccountNumber();

    /**
     * @param int $Blocked
     * @return $this
     */
    public function setBlocked($Blocked);

    /**
     * @return int
     */
    public function getBlocked();

    /**
     * @param string $CellularPhone
     * @return $this
     */
    public function setCellularPhone($CellularPhone);

    /**
     * @return string
     */
    public function getCellularPhone();

    /**
     * @param string $City
     * @return $this
     */
    public function setCity($City);

    /**
     * @return string
     */
    public function getCity();

    /**
     * @param string $ClubCode
     * @return $this
     */
    public function setClubCode($ClubCode);

    /**
     * @return string
     */
    public function getClubCode();

    /**
     * @param string $Country
     * @return $this
     */
    public function setCountry($Country);

    /**
     * @return string
     */
    public function getCountry();

    /**
     * @param string $County
     * @return $this
     */
    public function setCounty($County);

    /**
     * @return string
     */
    public function getCounty();

    /**
     * @param string $Currency
     * @return $this
     */
    public function setCurrency($Currency);

    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $CustomerId
     * @return $this
     */
    public function setCustomerId($CustomerId);

    /**
     * @return string
     */
    public function getCustomerId();

    /**
     * @param string $DiscountGroup
     * @return $this
     */
    public function setDiscountGroup($DiscountGroup);

    /**
     * @return string
     */
    public function getDiscountGroup();

    /**
     * @param string $Email
     * @return $this
     */
    public function setEmail($Email);

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @param string $FirstName
     * @return $this
     */
    public function setFirstName($FirstName);

    /**
     * @return string
     */
    public function getFirstName();

    /**
     * @param string $GuestType
     * @return $this
     */
    public function setGuestType($GuestType);

    /**
     * @return string
     */
    public function getGuestType();

    /**
     * @param string $nav_id
     * @return $this
     */
    public function setNavId($nav_id);

    /**
     * @return string
     */
    public function getNavId();

    /**
     * @param int $IncludeTax
     * @return $this
     */
    public function setIncludeTax($IncludeTax);

    /**
     * @return int
     */
    public function getIncludeTax();

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
     * @param string $LastName
     * @return $this
     */
    public function setLastName($LastName);

    /**
     * @return string
     */
    public function getLastName();

    /**
     * @param string $MiddleName
     * @return $this
     */
    public function setMiddleName($MiddleName);

    /**
     * @return string
     */
    public function getMiddleName();

    /**
     * @param string $Name
     * @return $this
     */
    public function setName($Name);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $NamePrefix
     * @return $this
     */
    public function setNamePrefix($NamePrefix);

    /**
     * @return string
     */
    public function getNamePrefix();

    /**
     * @param string $NameSuffix
     * @return $this
     */
    public function setNameSuffix($NameSuffix);

    /**
     * @return string
     */
    public function getNameSuffix();

    /**
     * @param string $PaymentTerms
     * @return $this
     */
    public function setPaymentTerms($PaymentTerms);

    /**
     * @return string
     */
    public function getPaymentTerms();

    /**
     * @param string $PhoneLocal
     * @return $this
     */
    public function setPhoneLocal($PhoneLocal);

    /**
     * @return string
     */
    public function getPhoneLocal();

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
     * @param string $ReceiptEmail
     * @return $this
     */
    public function setReceiptEmail($ReceiptEmail);

    /**
     * @return string
     */
    public function getReceiptEmail();

    /**
     * @param int $ReceiptOption
     * @return $this
     */
    public function setReceiptOption($ReceiptOption);

    /**
     * @return int
     */
    public function getReceiptOption();

    /**
     * @param string $SchemeCode
     * @return $this
     */
    public function setSchemeCode($SchemeCode);

    /**
     * @return string
     */
    public function getSchemeCode();

    /**
     * @param SendEmail $SendReceiptByEMail
     * @return $this
     */
    public function setSendReceiptByEMail($SendReceiptByEMail);

    /**
     * @return SendEmail
     */
    public function getSendReceiptByEMail();

    /**
     * @param string $ShippingLocation
     * @return $this
     */
    public function setShippingLocation($ShippingLocation);

    /**
     * @return string
     */
    public function getShippingLocation();

    /**
     * @param string $State
     * @return $this
     */
    public function setState($State);

    /**
     * @return string
     */
    public function getState();

    /**
     * @param string $Street
     * @return $this
     */
    public function setStreet($Street);

    /**
     * @return string
     */
    public function getStreet();

    /**
     * @param string $TaxGroup
     * @return $this
     */
    public function setTaxGroup($TaxGroup);

    /**
     * @return string
     */
    public function getTaxGroup();

    /**
     * @param string $URL
     * @return $this
     */
    public function setURL($URL);

    /**
     * @return string
     */
    public function getURL();

    /**
     * @param string $UserName
     * @return $this
     */
    public function setUserName($UserName);

    /**
     * @return string
     */
    public function getUserName();

    /**
     * @param string $ZipCode
     * @return $this
     */
    public function setZipCode($ZipCode);

    /**
     * @return string
     */
    public function getZipCode();

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

