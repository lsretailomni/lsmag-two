<?php

namespace Ls\Replication\Api\Data;

interface StoreInterface
{

    /**
     * @return Address
     */
    public function setAddress($Address);
    public function getAddress();
    /**
     * @return string
     */
    public function setCurrencyCode($CurrencyCode);
    public function getCurrencyCode();
    /**
     * @return string
     */
    public function setDescription($Description);
    public function getDescription();
    /**
     * @return float
     */
    public function setDistance($Distance);
    public function getDistance();
    /**
     * @return string
     */
    public function setId($Id);
    public function getId();
    /**
     * @return ArrayOfImageView
     */
    public function setImages($Images);
    public function getImages();
    /**
     * @return boolean
     */
    public function setIsClickAndCollect($IsClickAndCollect);
    public function getIsClickAndCollect();
    /**
     * @return float
     */
    public function setLatitude($Latitude);
    public function getLatitude();
    /**
     * @return float
     */
    public function setLongitude($Longitude);
    public function getLongitude();
    /**
     * @return string
     */
    public function setPhone($Phone);
    public function getPhone();
    /**
     * @return ArrayOfStoreHours
     */
    public function setStoreHours($StoreHours);
    public function getStoreHours();
    /**
     * @return ArrayOfStoreServices
     */
    public function setStoreServices($StoreServices);
    public function getStoreServices();
    /**
     * @return boolean
     */
    public function setCAC($CAC);
    public function getCAC();
    /**
     * @return string
     */
    public function setCity($City);
    public function getCity();
    /**
     * @return string
     */
    public function setCountry($Country);
    public function getCountry();
    /**
     * @return string
     */
    public function setCounty($County);
    public function getCounty();
    /**
     * @return string
     */
    public function setCultureName($CultureName);
    public function getCultureName();
    /**
     * @return string
     */
    public function setCurrency($Currency);
    public function getCurrency();
    /**
     * @return string
     */
    public function setDefaultCustAcct($DefaultCustAcct);
    public function getDefaultCustAcct();
    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return string
     */
    public function setFunctProfile($FunctProfile);
    public function getFunctProfile();
    /**
     * @return string
     */
    public function setName($Name);
    public function getName();
    /**
     * @return string
     */
    public function setState($State);
    public function getState();
    /**
     * @return string
     */
    public function setStreet($Street);
    public function getStreet();
    /**
     * @return string
     */
    public function setTaxGroup($TaxGroup);
    public function getTaxGroup();
    /**
     * @return int
     */
    public function setUserDefaultCustAcct($UserDefaultCustAcct);
    public function getUserDefaultCustAcct();
    /**
     * @return string
     */
    public function setZipCode($ZipCode);
    public function getZipCode();

}

