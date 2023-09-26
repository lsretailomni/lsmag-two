<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplCustomerInterface;

class ReplCustomer extends AbstractModel implements ReplCustomerInterface, IdentityInterface
{
    public const CACHE_TAG = 'ls_replication_repl_customer';

    protected $_cacheTag = 'ls_replication_repl_customer';

    protected $_eventPrefix = 'ls_replication_repl_customer';

    /**
     * @property ArrayOfCard $Cards
     */
    protected $Cards = null;

    /**
     * @property string $AccountNumber
     */
    protected $AccountNumber = null;

    /**
     * @property int $Blocked
     */
    protected $Blocked = null;

    /**
     * @property string $CellularPhone
     */
    protected $CellularPhone = null;

    /**
     * @property string $City
     */
    protected $City = null;

    /**
     * @property string $ClubCode
     */
    protected $ClubCode = null;

    /**
     * @property string $Country
     */
    protected $Country = null;

    /**
     * @property string $County
     */
    protected $County = null;

    /**
     * @property string $Currency
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
     * @property string $nav_id
     */
    protected $nav_id = null;

    /**
     * @property int $IncludeTax
     */
    protected $IncludeTax = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

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
     * @property string $PhoneLocal
     */
    protected $PhoneLocal = null;

    /**
     * @property string $ReceiptEmail
     */
    protected $ReceiptEmail = null;

    /**
     * @property int $ReceiptOption
     */
    protected $ReceiptOption = null;

    /**
     * @property string $SchemeCode
     */
    protected $SchemeCode = null;

    /**
     * @property string $State
     */
    protected $State = null;

    /**
     * @property string $Street
     */
    protected $Street = null;

    /**
     * @property string $TaxGroup
     */
    protected $TaxGroup = null;

    /**
     * @property string $URL
     */
    protected $URL = null;

    /**
     * @property string $UserName
     */
    protected $UserName = null;

    /**
     * @property string $ZipCode
     */
    protected $ZipCode = null;

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
     * @property string $checksum
     */
    protected $checksum = null;

    /**
     * @property string $processed_at
     */
    protected $processed_at = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplCustomer' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @param ArrayOfCard $Cards
     * @return $this
     */
    public function setCards($Cards)
    {
        $this->setData( 'Cards', $Cards );
        $this->Cards = $Cards;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ArrayOfCard
     */
    public function getCards()
    {
        return $this->getData( 'Cards' );
    }

    /**
     * @param string $AccountNumber
     * @return $this
     */
    public function setAccountNumber($AccountNumber)
    {
        $this->setData( 'AccountNumber', $AccountNumber );
        $this->AccountNumber = $AccountNumber;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->getData( 'AccountNumber' );
    }

    /**
     * @param int $Blocked
     * @return $this
     */
    public function setBlocked($Blocked)
    {
        $this->setData( 'Blocked', $Blocked );
        $this->Blocked = $Blocked;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getBlocked()
    {
        return $this->getData( 'Blocked' );
    }

    /**
     * @param string $CellularPhone
     * @return $this
     */
    public function setCellularPhone($CellularPhone)
    {
        $this->setData( 'CellularPhone', $CellularPhone );
        $this->CellularPhone = $CellularPhone;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCellularPhone()
    {
        return $this->getData( 'CellularPhone' );
    }

    /**
     * @param string $City
     * @return $this
     */
    public function setCity($City)
    {
        $this->setData( 'City', $City );
        $this->City = $City;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->getData( 'City' );
    }

    /**
     * @param string $ClubCode
     * @return $this
     */
    public function setClubCode($ClubCode)
    {
        $this->setData( 'ClubCode', $ClubCode );
        $this->ClubCode = $ClubCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getClubCode()
    {
        return $this->getData( 'ClubCode' );
    }

    /**
     * @param string $Country
     * @return $this
     */
    public function setCountry($Country)
    {
        $this->setData( 'Country', $Country );
        $this->Country = $Country;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->getData( 'Country' );
    }

    /**
     * @param string $County
     * @return $this
     */
    public function setCounty($County)
    {
        $this->setData( 'County', $County );
        $this->County = $County;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCounty()
    {
        return $this->getData( 'County' );
    }

    /**
     * @param string $Currency
     * @return $this
     */
    public function setCurrency($Currency)
    {
        $this->setData( 'Currency', $Currency );
        $this->Currency = $Currency;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->getData( 'Currency' );
    }

    /**
     * @param string $Email
     * @return $this
     */
    public function setEmail($Email)
    {
        $this->setData( 'Email', $Email );
        $this->Email = $Email;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->getData( 'Email' );
    }

    /**
     * @param string $FirstName
     * @return $this
     */
    public function setFirstName($FirstName)
    {
        $this->setData( 'FirstName', $FirstName );
        $this->FirstName = $FirstName;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->getData( 'FirstName' );
    }

    /**
     * @param string $nav_id
     * @return $this
     */
    public function setNavId($nav_id)
    {
        $this->setData( 'nav_id', $nav_id );
        $this->nav_id = $nav_id;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getNavId()
    {
        return $this->getData( 'nav_id' );
    }

    /**
     * @param int $IncludeTax
     * @return $this
     */
    public function setIncludeTax($IncludeTax)
    {
        $this->setData( 'IncludeTax', $IncludeTax );
        $this->IncludeTax = $IncludeTax;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getIncludeTax()
    {
        return $this->getData( 'IncludeTax' );
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
     * @param string $LastName
     * @return $this
     */
    public function setLastName($LastName)
    {
        $this->setData( 'LastName', $LastName );
        $this->LastName = $LastName;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->getData( 'LastName' );
    }

    /**
     * @param string $MiddleName
     * @return $this
     */
    public function setMiddleName($MiddleName)
    {
        $this->setData( 'MiddleName', $MiddleName );
        $this->MiddleName = $MiddleName;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getMiddleName()
    {
        return $this->getData( 'MiddleName' );
    }

    /**
     * @param string $Name
     * @return $this
     */
    public function setName($Name)
    {
        $this->setData( 'Name', $Name );
        $this->Name = $Name;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData( 'Name' );
    }

    /**
     * @param string $NamePrefix
     * @return $this
     */
    public function setNamePrefix($NamePrefix)
    {
        $this->setData( 'NamePrefix', $NamePrefix );
        $this->NamePrefix = $NamePrefix;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getNamePrefix()
    {
        return $this->getData( 'NamePrefix' );
    }

    /**
     * @param string $NameSuffix
     * @return $this
     */
    public function setNameSuffix($NameSuffix)
    {
        $this->setData( 'NameSuffix', $NameSuffix );
        $this->NameSuffix = $NameSuffix;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getNameSuffix()
    {
        return $this->getData( 'NameSuffix' );
    }

    /**
     * @param string $PhoneLocal
     * @return $this
     */
    public function setPhoneLocal($PhoneLocal)
    {
        $this->setData( 'PhoneLocal', $PhoneLocal );
        $this->PhoneLocal = $PhoneLocal;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneLocal()
    {
        return $this->getData( 'PhoneLocal' );
    }

    /**
     * @param string $ReceiptEmail
     * @return $this
     */
    public function setReceiptEmail($ReceiptEmail)
    {
        $this->setData( 'ReceiptEmail', $ReceiptEmail );
        $this->ReceiptEmail = $ReceiptEmail;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiptEmail()
    {
        return $this->getData( 'ReceiptEmail' );
    }

    /**
     * @param int $ReceiptOption
     * @return $this
     */
    public function setReceiptOption($ReceiptOption)
    {
        $this->setData( 'ReceiptOption', $ReceiptOption );
        $this->ReceiptOption = $ReceiptOption;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getReceiptOption()
    {
        return $this->getData( 'ReceiptOption' );
    }

    /**
     * @param string $SchemeCode
     * @return $this
     */
    public function setSchemeCode($SchemeCode)
    {
        $this->setData( 'SchemeCode', $SchemeCode );
        $this->SchemeCode = $SchemeCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getSchemeCode()
    {
        return $this->getData( 'SchemeCode' );
    }

    /**
     * @param string $State
     * @return $this
     */
    public function setState($State)
    {
        $this->setData( 'State', $State );
        $this->State = $State;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->getData( 'State' );
    }

    /**
     * @param string $Street
     * @return $this
     */
    public function setStreet($Street)
    {
        $this->setData( 'Street', $Street );
        $this->Street = $Street;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->getData( 'Street' );
    }

    /**
     * @param string $TaxGroup
     * @return $this
     */
    public function setTaxGroup($TaxGroup)
    {
        $this->setData( 'TaxGroup', $TaxGroup );
        $this->TaxGroup = $TaxGroup;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getTaxGroup()
    {
        return $this->getData( 'TaxGroup' );
    }

    /**
     * @param string $URL
     * @return $this
     */
    public function setURL($URL)
    {
        $this->setData( 'URL', $URL );
        $this->URL = $URL;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        return $this->getData( 'URL' );
    }

    /**
     * @param string $UserName
     * @return $this
     */
    public function setUserName($UserName)
    {
        $this->setData( 'UserName', $UserName );
        $this->UserName = $UserName;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->getData( 'UserName' );
    }

    /**
     * @param string $ZipCode
     * @return $this
     */
    public function setZipCode($ZipCode)
    {
        $this->setData( 'ZipCode', $ZipCode );
        $this->ZipCode = $ZipCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->getData( 'ZipCode' );
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

