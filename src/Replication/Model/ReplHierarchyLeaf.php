<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplHierarchyLeafInterface;

class ReplHierarchyLeaf extends AbstractModel implements ReplHierarchyLeafInterface, IdentityInterface
{

    public const CACHE_TAG = 'ls_replication_repl_hierarchy_leaf';

    protected $_cacheTag = 'ls_replication_repl_hierarchy_leaf';

    protected $_eventPrefix = 'ls_replication_repl_hierarchy_leaf';

    /**
     * @property float $DealPrice
     */
    protected $DealPrice = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $HierarchyCode
     */
    protected $HierarchyCode = null;

    /**
     * @property string $nav_id
     */
    protected $nav_id = null;

    /**
     * @property string $ImageId
     */
    protected $ImageId = null;

    /**
     * @property boolean $IsActive
     */
    protected $IsActive = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property boolean $IsMemberClub
     */
    protected $IsMemberClub = null;

    /**
     * @property string $ItemUOM
     */
    protected $ItemUOM = null;

    /**
     * @property string $MemberValue
     */
    protected $MemberValue = null;

    /**
     * @property string $NodeId
     */
    protected $NodeId = null;

    /**
     * @property int $SortOrder
     */
    protected $SortOrder = null;

    /**
     * @property HierarchyLeafType $Type
     */
    protected $Type = null;

    /**
     * @property string $ValidationPeriod
     */
    protected $ValidationPeriod = null;

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
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplHierarchyLeaf' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @param float $DealPrice
     * @return $this
     */
    public function setDealPrice($DealPrice)
    {
        $this->setData( 'DealPrice', $DealPrice );
        $this->DealPrice = $DealPrice;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getDealPrice()
    {
        return $this->getData( 'DealPrice' );
    }

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->setData( 'Description', $Description );
        $this->Description = $Description;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getData( 'Description' );
    }

    /**
     * @param string $HierarchyCode
     * @return $this
     */
    public function setHierarchyCode($HierarchyCode)
    {
        $this->setData( 'HierarchyCode', $HierarchyCode );
        $this->HierarchyCode = $HierarchyCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getHierarchyCode()
    {
        return $this->getData( 'HierarchyCode' );
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
     * @param string $ImageId
     * @return $this
     */
    public function setImageId($ImageId)
    {
        $this->setData( 'ImageId', $ImageId );
        $this->ImageId = $ImageId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getImageId()
    {
        return $this->getData( 'ImageId' );
    }

    /**
     * @param boolean $IsActive
     * @return $this
     */
    public function setIsActive($IsActive)
    {
        $this->setData( 'IsActive', $IsActive );
        $this->IsActive = $IsActive;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->getData( 'IsActive' );
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
     * @param boolean $IsMemberClub
     * @return $this
     */
    public function setIsMemberClub($IsMemberClub)
    {
        $this->setData( 'IsMemberClub', $IsMemberClub );
        $this->IsMemberClub = $IsMemberClub;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsMemberClub()
    {
        return $this->getData( 'IsMemberClub' );
    }

    /**
     * @param string $ItemUOM
     * @return $this
     */
    public function setItemUOM($ItemUOM)
    {
        $this->setData( 'ItemUOM', $ItemUOM );
        $this->ItemUOM = $ItemUOM;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getItemUOM()
    {
        return $this->getData( 'ItemUOM' );
    }

    /**
     * @param string $MemberValue
     * @return $this
     */
    public function setMemberValue($MemberValue)
    {
        $this->setData( 'MemberValue', $MemberValue );
        $this->MemberValue = $MemberValue;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getMemberValue()
    {
        return $this->getData( 'MemberValue' );
    }

    /**
     * @param string $NodeId
     * @return $this
     */
    public function setNodeId($NodeId)
    {
        $this->setData( 'NodeId', $NodeId );
        $this->NodeId = $NodeId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getNodeId()
    {
        return $this->getData( 'NodeId' );
    }

    /**
     * @param int $SortOrder
     * @return $this
     */
    public function setSortOrder($SortOrder)
    {
        $this->setData( 'SortOrder', $SortOrder );
        $this->SortOrder = $SortOrder;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->getData( 'SortOrder' );
    }

    /**
     * @param HierarchyLeafType $Type
     * @return $this
     */
    public function setType($Type)
    {
        $this->setData( 'Type', $Type );
        $this->Type = $Type;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return HierarchyLeafType
     */
    public function getType()
    {
        return $this->getData( 'Type' );
    }

    /**
     * @param string $ValidationPeriod
     * @return $this
     */
    public function setValidationPeriod($ValidationPeriod)
    {
        $this->setData( 'ValidationPeriod', $ValidationPeriod );
        $this->ValidationPeriod = $ValidationPeriod;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getValidationPeriod()
    {
        return $this->getData( 'ValidationPeriod' );
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

