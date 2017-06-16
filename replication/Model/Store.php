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

    /**
     * @return $this
     */
    public function setAddress($Address)
    {
        $this->setData( 'Address', $Address );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getAddress()
    {
        return $this->Address;
    }

    /**
     * @return $this
     */
    public function setCurrencyCode($CurrencyCode)
    {
        $this->setData( 'CurrencyCode', $CurrencyCode );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCurrencyCode()
    {
        return $this->CurrencyCode;
    }

    /**
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->setData( 'Description', $Description );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * @return $this
     */
    public function setDistance($Distance)
    {
        $this->setData( 'Distance', $Distance );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDistance()
    {
        return $this->Distance;
    }

    /**
     * @return $this
     */
    public function setId($Id)
    {
        $this->setData( 'Id', $Id );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getId()
    {
        return $this->Id;
    }

    /**
     * @return $this
     */
    public function setImages($Images)
    {
        $this->setData( 'Images', $Images );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getImages()
    {
        return $this->Images;
    }

    /**
     * @return $this
     */
    public function setIsClickAndCollect($IsClickAndCollect)
    {
        $this->setData( 'IsClickAndCollect', $IsClickAndCollect );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getIsClickAndCollect()
    {
        return $this->IsClickAndCollect;
    }

    /**
     * @return $this
     */
    public function setLatitude($Latitude)
    {
        $this->setData( 'Latitude', $Latitude );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getLatitude()
    {
        return $this->Latitude;
    }

    /**
     * @return $this
     */
    public function setLongitude($Longitude)
    {
        $this->setData( 'Longitude', $Longitude );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getLongitude()
    {
        return $this->Longitude;
    }

    /**
     * @return $this
     */
    public function setPhone($Phone)
    {
        $this->setData( 'Phone', $Phone );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getPhone()
    {
        return $this->Phone;
    }

    /**
     * @return $this
     */
    public function setStoreHours($StoreHours)
    {
        $this->setData( 'StoreHours', $StoreHours );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getStoreHours()
    {
        return $this->StoreHours;
    }

    /**
     * @return $this
     */
    public function setStoreServices($StoreServices)
    {
        $this->setData( 'StoreServices', $StoreServices );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getStoreServices()
    {
        return $this->StoreServices;
    }

    /**
     * @return $this
     */
    public function setCAC($CAC)
    {
        $this->setData( 'CAC', $CAC );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCAC()
    {
        return $this->CAC;
    }

    /**
     * @return $this
     */
    public function setCity($City)
    {
        $this->setData( 'City', $City );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCity()
    {
        return $this->City;
    }

    /**
     * @return $this
     */
    public function setCountry($Country)
    {
        $this->setData( 'Country', $Country );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCountry()
    {
        return $this->Country;
    }

    /**
     * @return $this
     */
    public function setCounty($County)
    {
        $this->setData( 'County', $County );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCounty()
    {
        return $this->County;
    }

    /**
     * @return $this
     */
    public function setCultureName($CultureName)
    {
        $this->setData( 'CultureName', $CultureName );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCultureName()
    {
        return $this->CultureName;
    }

    /**
     * @return $this
     */
    public function setCurrency($Currency)
    {
        $this->setData( 'Currency', $Currency );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCurrency()
    {
        return $this->Currency;
    }

    /**
     * @return $this
     */
    public function setDefaultCustAcct($DefaultCustAcct)
    {
        $this->setData( 'DefaultCustAcct', $DefaultCustAcct );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDefaultCustAcct()
    {
        return $this->DefaultCustAcct;
    }

    /**
     * @return $this
     */
    public function setDel($Del)
    {
        $this->setData( 'Del', $Del );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    /**
     * @return $this
     */
    public function setFunctProfile($FunctProfile)
    {
        $this->setData( 'FunctProfile', $FunctProfile );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getFunctProfile()
    {
        return $this->FunctProfile;
    }

    /**
     * @return $this
     */
    public function setName($Name)
    {
        $this->setData( 'Name', $Name );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getName()
    {
        return $this->Name;
    }

    /**
     * @return $this
     */
    public function setState($State)
    {
        $this->setData( 'State', $State );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getState()
    {
        return $this->State;
    }

    /**
     * @return $this
     */
    public function setStreet($Street)
    {
        $this->setData( 'Street', $Street );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getStreet()
    {
        return $this->Street;
    }

    /**
     * @return $this
     */
    public function setTaxGroup($TaxGroup)
    {
        $this->setData( 'TaxGroup', $TaxGroup );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getTaxGroup()
    {
        return $this->TaxGroup;
    }

    /**
     * @return $this
     */
    public function setUserDefaultCustAcct($UserDefaultCustAcct)
    {
        $this->setData( 'UserDefaultCustAcct', $UserDefaultCustAcct );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getUserDefaultCustAcct()
    {
        return $this->UserDefaultCustAcct;
    }

    /**
     * @return $this
     */
    public function setZipCode($ZipCode)
    {
        $this->setData( 'ZipCode', $ZipCode );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getZipCode()
    {
        return $this->ZipCode;
    }


}

