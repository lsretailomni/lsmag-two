<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\StoreInterface;

class Store extends AbstractModel implements StoreInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_store';

    protected $_cacheTag = 'lsr_replication_store';

    protected $_eventPrefix = 'lsr_replication_store';

    protected $Address = null;

    protected $CurrencyCode = null;

    protected $Description = null;

    protected $Distance = null;

    protected $Id = null;

    protected $Images = null;

    protected $IsClickAndCollect = null;

    protected $Latitude = null;

    protected $Longitude = null;

    protected $Phone = null;

    protected $StoreHours = null;

    protected $StoreServices = null;

    protected $CAC = null;

    protected $City = null;

    protected $Country = null;

    protected $County = null;

    protected $CultureName = null;

    protected $Currency = null;

    protected $DefaultCustAcct = null;

    protected $Del = null;

    protected $FunctProfile = null;

    protected $Name = null;

    protected $State = null;

    protected $Street = null;

    protected $TaxGroup = null;

    protected $UserDefaultCustAcct = null;

    protected $ZipCode = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Store' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    public function setAddress($Address)
    {
        $this->Address = $Address;
        return $this;
    }

    public function getAddress()
    {
        return $this->Address;
    }

    public function setCurrencyCode($CurrencyCode)
    {
        $this->CurrencyCode = $CurrencyCode;
        return $this;
    }

    public function getCurrencyCode()
    {
        return $this->CurrencyCode;
    }

    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    public function getDescription()
    {
        return $this->Description;
    }

    public function setDistance($Distance)
    {
        $this->Distance = $Distance;
        return $this;
    }

    public function getDistance()
    {
        return $this->Distance;
    }

    public function setId($Id)
    {
        $this->Id = $Id;
        return $this;
    }

    public function getId()
    {
        return $this->Id;
    }

    public function setImages($Images)
    {
        $this->Images = $Images;
        return $this;
    }

    public function getImages()
    {
        return $this->Images;
    }

    public function setIsClickAndCollect($IsClickAndCollect)
    {
        $this->IsClickAndCollect = $IsClickAndCollect;
        return $this;
    }

    public function getIsClickAndCollect()
    {
        return $this->IsClickAndCollect;
    }

    public function setLatitude($Latitude)
    {
        $this->Latitude = $Latitude;
        return $this;
    }

    public function getLatitude()
    {
        return $this->Latitude;
    }

    public function setLongitude($Longitude)
    {
        $this->Longitude = $Longitude;
        return $this;
    }

    public function getLongitude()
    {
        return $this->Longitude;
    }

    public function setPhone($Phone)
    {
        $this->Phone = $Phone;
        return $this;
    }

    public function getPhone()
    {
        return $this->Phone;
    }

    public function setStoreHours($StoreHours)
    {
        $this->StoreHours = $StoreHours;
        return $this;
    }

    public function getStoreHours()
    {
        return $this->StoreHours;
    }

    public function setStoreServices($StoreServices)
    {
        $this->StoreServices = $StoreServices;
        return $this;
    }

    public function getStoreServices()
    {
        return $this->StoreServices;
    }

    public function setCAC($CAC)
    {
        $this->CAC = $CAC;
        return $this;
    }

    public function getCAC()
    {
        return $this->CAC;
    }

    public function setCity($City)
    {
        $this->City = $City;
        return $this;
    }

    public function getCity()
    {
        return $this->City;
    }

    public function setCountry($Country)
    {
        $this->Country = $Country;
        return $this;
    }

    public function getCountry()
    {
        return $this->Country;
    }

    public function setCounty($County)
    {
        $this->County = $County;
        return $this;
    }

    public function getCounty()
    {
        return $this->County;
    }

    public function setCultureName($CultureName)
    {
        $this->CultureName = $CultureName;
        return $this;
    }

    public function getCultureName()
    {
        return $this->CultureName;
    }

    public function setCurrency($Currency)
    {
        $this->Currency = $Currency;
        return $this;
    }

    public function getCurrency()
    {
        return $this->Currency;
    }

    public function setDefaultCustAcct($DefaultCustAcct)
    {
        $this->DefaultCustAcct = $DefaultCustAcct;
        return $this;
    }

    public function getDefaultCustAcct()
    {
        return $this->DefaultCustAcct;
    }

    public function setDel($Del)
    {
        $this->Del = $Del;
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    public function setFunctProfile($FunctProfile)
    {
        $this->FunctProfile = $FunctProfile;
        return $this;
    }

    public function getFunctProfile()
    {
        return $this->FunctProfile;
    }

    public function setName($Name)
    {
        $this->Name = $Name;
        return $this;
    }

    public function getName()
    {
        return $this->Name;
    }

    public function setState($State)
    {
        $this->State = $State;
        return $this;
    }

    public function getState()
    {
        return $this->State;
    }

    public function setStreet($Street)
    {
        $this->Street = $Street;
        return $this;
    }

    public function getStreet()
    {
        return $this->Street;
    }

    public function setTaxGroup($TaxGroup)
    {
        $this->TaxGroup = $TaxGroup;
        return $this;
    }

    public function getTaxGroup()
    {
        return $this->TaxGroup;
    }

    public function setUserDefaultCustAcct($UserDefaultCustAcct)
    {
        $this->UserDefaultCustAcct = $UserDefaultCustAcct;
        return $this;
    }

    public function getUserDefaultCustAcct()
    {
        return $this->UserDefaultCustAcct;
    }

    public function setZipCode($ZipCode)
    {
        $this->ZipCode = $ZipCode;
        return $this;
    }

    public function getZipCode()
    {
        return $this->ZipCode;
    }


}

