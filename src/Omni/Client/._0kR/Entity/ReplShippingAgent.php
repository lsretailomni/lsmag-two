<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ReplShippingAgent
{
    /**
     * @property ArrayOfShippingAgentService $Services
     */
    protected $Services = null;

    /**
     * @property string $AccountNo
     */
    protected $AccountNo = null;

    /**
     * @property string $Id
     */
    protected $Id = null;

    /**
     * @property string $InternetAddress
     */
    protected $InternetAddress = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property string $Name
     */
    protected $Name = null;

    /**
     * @property string $scope
     */
    protected $scope = null;

    /**
     * @property int $scope_id
     */
    protected $scope_id = null;

    /**
     * @param ArrayOfShippingAgentService $Services
     * @return $this
     */
    public function setServices($Services)
    {
        $this->Services = $Services;
        return $this;
    }

    /**
     * @return ArrayOfShippingAgentService
     */
    public function getServices()
    {
        return $this->Services;
    }

    /**
     * @param string $AccountNo
     * @return $this
     */
    public function setAccountNo($AccountNo)
    {
        $this->AccountNo = $AccountNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccountNo()
    {
        return $this->AccountNo;
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
     * @param string $InternetAddress
     * @return $this
     */
    public function setInternetAddress($InternetAddress)
    {
        $this->InternetAddress = $InternetAddress;
        return $this;
    }

    /**
     * @return string
     */
    public function getInternetAddress()
    {
        return $this->InternetAddress;
    }

    /**
     * @param boolean $IsDeleted
     * @return $this
     */
    public function setIsDeleted($IsDeleted)
    {
        $this->IsDeleted = $IsDeleted;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->IsDeleted;
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
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param int $scope_id
     * @return $this
     */
    public function setScopeId($scope_id)
    {
        $this->scope_id = $scope_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        return $this->scope_id;
    }
}
