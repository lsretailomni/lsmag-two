<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\HierarchyType;
use Ls\Omni\Exception\InvalidEnumException;

class ReplHierarchy
{
    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $Id
     */
    protected $Id = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property int $Priority
     */
    protected $Priority = null;

    /**
     * @property string $SalesType
     */
    protected $SalesType = null;

    /**
     * @property string $StartDate
     */
    protected $StartDate = null;

    /**
     * @property HierarchyType $Type
     */
    protected $Type = null;

    /**
     * @property string $ValidationScheduleId
     */
    protected $ValidationScheduleId = null;

    /**
     * @property string $scope
     */
    protected $scope = null;

    /**
     * @property int $scope_id
     */
    protected $scope_id = null;

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
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
     * @param int $Priority
     * @return $this
     */
    public function setPriority($Priority)
    {
        $this->Priority = $Priority;
        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->Priority;
    }

    /**
     * @param string $SalesType
     * @return $this
     */
    public function setSalesType($SalesType)
    {
        $this->SalesType = $SalesType;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalesType()
    {
        return $this->SalesType;
    }

    /**
     * @param string $StartDate
     * @return $this
     */
    public function setStartDate($StartDate)
    {
        $this->StartDate = $StartDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        return $this->StartDate;
    }

    /**
     * @param HierarchyType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof HierarchyType ) {
            if ( HierarchyType::isValid( $Type ) )
                $Type = new HierarchyType( $Type );
            elseif ( HierarchyType::isValidKey( $Type ) )
                $Type = new HierarchyType( constant( "HierarchyType::$Type" ) );
            elseif ( ! $Type instanceof HierarchyType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();

        return $this;
    }

    /**
     * @return HierarchyType
     */
    public function getType()
    {
        return $this->Type;
    }

    /**
     * @param string $ValidationScheduleId
     * @return $this
     */
    public function setValidationScheduleId($ValidationScheduleId)
    {
        $this->ValidationScheduleId = $ValidationScheduleId;
        return $this;
    }

    /**
     * @return string
     */
    public function getValidationScheduleId()
    {
        return $this->ValidationScheduleId;
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
