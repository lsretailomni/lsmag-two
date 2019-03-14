<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\AddressType;
use Ls\Omni\Exception\InvalidEnumException;

class Address
{

    /**
     * @property string $Address1
     */
    protected $Address1 = null;

    /**
     * @property string $Address2
     */
    protected $Address2 = null;

    /**
     * @property string $CellPhoneNumber
     */
    protected $CellPhoneNumber = null;

    /**
     * @property string $City
     */
    protected $City = null;

    /**
     * @property string $Country
     */
    protected $Country = null;

    /**
     * @property string $HouseNo
     */
    protected $HouseNo = null;

    /**
     * @property string $Id
     */
    protected $Id = null;

    /**
     * @property string $PhoneNumber
     */
    protected $PhoneNumber = null;

    /**
     * @property string $PostCode
     */
    protected $PostCode = null;

    /**
     * @property string $StateProvinceRegion
     */
    protected $StateProvinceRegion = null;

    /**
     * @property AddressType $Type
     */
    protected $Type = null;

    /**
     * @param string $Address1
     * @return $this
     */
    public function setAddress1($Address1)
    {
        $this->Address1 = $Address1;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress1()
    {
        return $this->Address1;
    }

    /**
     * @param string $Address2
     * @return $this
     */
    public function setAddress2($Address2)
    {
        $this->Address2 = $Address2;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->Address2;
    }

    /**
     * @param string $CellPhoneNumber
     * @return $this
     */
    public function setCellPhoneNumber($CellPhoneNumber)
    {
        $this->CellPhoneNumber = $CellPhoneNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getCellPhoneNumber()
    {
        return $this->CellPhoneNumber;
    }

    /**
     * @param string $City
     * @return $this
     */
    public function setCity($City)
    {
        $this->City = $City;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->City;
    }

    /**
     * @param string $Country
     * @return $this
     */
    public function setCountry($Country)
    {
        $this->Country = $Country;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->Country;
    }

    /**
     * @param string $HouseNo
     * @return $this
     */
    public function setHouseNo($HouseNo)
    {
        $this->HouseNo = $HouseNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getHouseNo()
    {
        return $this->HouseNo;
    }

    /**
     * @param string $Id
     * @return $this
     */
    public function setId($Id)
    {
        $this->Id = $Id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->Id;
    }

    /**
     * @param string $PhoneNumber
     * @return $this
     */
    public function setPhoneNumber($PhoneNumber)
    {
        $this->PhoneNumber = $PhoneNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->PhoneNumber;
    }

    /**
     * @param string $PostCode
     * @return $this
     */
    public function setPostCode($PostCode)
    {
        $this->PostCode = $PostCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostCode()
    {
        return $this->PostCode;
    }

    /**
     * @param string $StateProvinceRegion
     * @return $this
     */
    public function setStateProvinceRegion($StateProvinceRegion)
    {
        $this->StateProvinceRegion = $StateProvinceRegion;
        return $this;
    }

    /**
     * @return string
     */
    public function getStateProvinceRegion()
    {
        return $this->StateProvinceRegion;
    }

    /**
     * @param AddressType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof AddressType ) {
            if ( AddressType::isValid( $Type ) ) 
                $Type = new AddressType( $Type );
            elseif ( AddressType::isValidKey( $Type ) ) 
                $Type = new AddressType( constant( "AddressType::$Type" ) );
            elseif ( ! $Type instanceof AddressType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();

        return $this;
    }

    /**
     * @return AddressType
     */
    public function getType()
    {
        return $this->Type;
    }


}

