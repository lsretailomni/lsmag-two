<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class Customer extends Entity
{
    /**
     * @property Address $Address
     */
    protected $Address = null;

    /**
     * @property Currency $Currency
     */
    protected $Currency = null;

    /**
     * @property string $Email
     */
    protected $Email = null;

    /**
     * @property string $FirstName
     */
    protected $FirstName = null;

    /**
     * @property int $InclTax
     */
    protected $InclTax = null;

    /**
     * @property boolean $IsBlocked
     */
    protected $IsBlocked = null;

    /**
     * @property string $LastName
     */
    protected $LastName = null;

    /**
     * @property string $MiddleName
     */
    protected $MiddleName = null;

    /**
     * @property string $Name
     */
    protected $Name = null;

    /**
     * @property string $NamePrefix
     */
    protected $NamePrefix = null;

    /**
     * @property string $NameSuffix
     */
    protected $NameSuffix = null;

    /**
     * @property string $ReceiptEmail
     */
    protected $ReceiptEmail = null;

    /**
     * @property int $ReceiptOption
     */
    protected $ReceiptOption = null;

    /**
     * @property string $TaxGroup
     */
    protected $TaxGroup = null;

    /**
     * @property string $Url
     */
    protected $Url = null;

    /**
     * @param Address $Address
     * @return $this
     */
    public function setAddress($Address)
    {
        $this->Address = $Address;
        return $this;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->Address;
    }

    /**
     * @param Currency $Currency
     * @return $this
     */
    public function setCurrency($Currency)
    {
        $this->Currency = $Currency;
        return $this;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->Currency;
    }

    /**
     * @param string $Email
     * @return $this
     */
    public function setEmail($Email)
    {
        $this->Email = $Email;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->Email;
    }

    /**
     * @param string $FirstName
     * @return $this
     */
    public function setFirstName($FirstName)
    {
        $this->FirstName = $FirstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->FirstName;
    }

    /**
     * @param int $InclTax
     * @return $this
     */
    public function setInclTax($InclTax)
    {
        $this->InclTax = $InclTax;
        return $this;
    }

    /**
     * @return int
     */
    public function getInclTax()
    {
        return $this->InclTax;
    }

    /**
     * @param boolean $IsBlocked
     * @return $this
     */
    public function setIsBlocked($IsBlocked)
    {
        $this->IsBlocked = $IsBlocked;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsBlocked()
    {
        return $this->IsBlocked;
    }

    /**
     * @param string $LastName
     * @return $this
     */
    public function setLastName($LastName)
    {
        $this->LastName = $LastName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->LastName;
    }

    /**
     * @param string $MiddleName
     * @return $this
     */
    public function setMiddleName($MiddleName)
    {
        $this->MiddleName = $MiddleName;
        return $this;
    }

    /**
     * @return string
     */
    public function getMiddleName()
    {
        return $this->MiddleName;
    }

    /**
     * @param string $Name
     * @return $this
     */
    public function setName($Name)
    {
        $this->Name = $Name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * @param string $NamePrefix
     * @return $this
     */
    public function setNamePrefix($NamePrefix)
    {
        $this->NamePrefix = $NamePrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamePrefix()
    {
        return $this->NamePrefix;
    }

    /**
     * @param string $NameSuffix
     * @return $this
     */
    public function setNameSuffix($NameSuffix)
    {
        $this->NameSuffix = $NameSuffix;
        return $this;
    }

    /**
     * @return string
     */
    public function getNameSuffix()
    {
        return $this->NameSuffix;
    }

    /**
     * @param string $ReceiptEmail
     * @return $this
     */
    public function setReceiptEmail($ReceiptEmail)
    {
        $this->ReceiptEmail = $ReceiptEmail;
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiptEmail()
    {
        return $this->ReceiptEmail;
    }

    /**
     * @param int $ReceiptOption
     * @return $this
     */
    public function setReceiptOption($ReceiptOption)
    {
        $this->ReceiptOption = $ReceiptOption;
        return $this;
    }

    /**
     * @return int
     */
    public function getReceiptOption()
    {
        return $this->ReceiptOption;
    }

    /**
     * @param string $TaxGroup
     * @return $this
     */
    public function setTaxGroup($TaxGroup)
    {
        $this->TaxGroup = $TaxGroup;
        return $this;
    }

    /**
     * @return string
     */
    public function getTaxGroup()
    {
        return $this->TaxGroup;
    }

    /**
     * @param string $Url
     * @return $this
     */
    public function setUrl($Url)
    {
        $this->Url = $Url;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->Url;
    }
}

