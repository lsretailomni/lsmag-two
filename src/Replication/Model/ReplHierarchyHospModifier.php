<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplHierarchyHospModifierInterface;

class ReplHierarchyHospModifier extends AbstractModel implements ReplHierarchyHospModifierInterface, IdentityInterface
{

    const CACHE_TAG = 'ls_replication_repl_hierarchy_hosp_modifier';

    protected $_cacheTag = 'ls_replication_repl_hierarchy_hosp_modifier';

    protected $_eventPrefix = 'ls_replication_repl_hierarchy_hosp_modifier';

    /**
     * @property boolean $AlwaysCharge
     */
    protected $AlwaysCharge = null;

    /**
     * @property float $AmountPercent
     */
    protected $AmountPercent = null;

    /**
     * @property string $Code
     */
    protected $Code = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $HierarchyCode
     */
    protected $HierarchyCode = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property string $ItemNo
     */
    protected $ItemNo = null;

    /**
     * @property int $MaxSelection
     */
    protected $MaxSelection = null;

    /**
     * @property int $MinSelection
     */
    protected $MinSelection = null;

    /**
     * @property string $ParentItem
     */
    protected $ParentItem = null;

    /**
     * @property string $ParentNode
     */
    protected $ParentNode = null;

    /**
     * @property ModifierPriceType $PriceType
     */
    protected $PriceType = null;

    /**
     * @property string $SubCode
     */
    protected $SubCode = null;

    /**
     * @property string $UnitOfMeasure
     */
    protected $UnitOfMeasure = null;

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
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplHierarchyHospModifier' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @param boolean $AlwaysCharge
     * @return $this
     */
    public function setAlwaysCharge($AlwaysCharge)
    {
        $this->setData( 'AlwaysCharge', $AlwaysCharge );
        $this->AlwaysCharge = $AlwaysCharge;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAlwaysCharge()
    {
        return $this->getData( 'AlwaysCharge' );
    }

    /**
     * @param float $AmountPercent
     * @return $this
     */
    public function setAmountPercent($AmountPercent)
    {
        $this->setData( 'AmountPercent', $AmountPercent );
        $this->AmountPercent = $AmountPercent;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountPercent()
    {
        return $this->getData( 'AmountPercent' );
    }

    /**
     * @param string $Code
     * @return $this
     */
    public function setCode($Code)
    {
        $this->setData( 'Code', $Code );
        $this->Code = $Code;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->getData( 'Code' );
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
     * @param string $ItemNo
     * @return $this
     */
    public function setItemNo($ItemNo)
    {
        $this->setData( 'ItemNo', $ItemNo );
        $this->ItemNo = $ItemNo;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getItemNo()
    {
        return $this->getData( 'ItemNo' );
    }

    /**
     * @param int $MaxSelection
     * @return $this
     */
    public function setMaxSelection($MaxSelection)
    {
        $this->setData( 'MaxSelection', $MaxSelection );
        $this->MaxSelection = $MaxSelection;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxSelection()
    {
        return $this->getData( 'MaxSelection' );
    }

    /**
     * @param int $MinSelection
     * @return $this
     */
    public function setMinSelection($MinSelection)
    {
        $this->setData( 'MinSelection', $MinSelection );
        $this->MinSelection = $MinSelection;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getMinSelection()
    {
        return $this->getData( 'MinSelection' );
    }

    /**
     * @param string $ParentItem
     * @return $this
     */
    public function setParentItem($ParentItem)
    {
        $this->setData( 'ParentItem', $ParentItem );
        $this->ParentItem = $ParentItem;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getParentItem()
    {
        return $this->getData( 'ParentItem' );
    }

    /**
     * @param string $ParentNode
     * @return $this
     */
    public function setParentNode($ParentNode)
    {
        $this->setData( 'ParentNode', $ParentNode );
        $this->ParentNode = $ParentNode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getParentNode()
    {
        return $this->getData( 'ParentNode' );
    }

    /**
     * @param ModifierPriceType $PriceType
     * @return $this
     */
    public function setPriceType($PriceType)
    {
        $this->setData( 'PriceType', $PriceType );
        $this->PriceType = $PriceType;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return ModifierPriceType
     */
    public function getPriceType()
    {
        return $this->getData( 'PriceType' );
    }

    /**
     * @param string $SubCode
     * @return $this
     */
    public function setSubCode($SubCode)
    {
        $this->setData( 'SubCode', $SubCode );
        $this->SubCode = $SubCode;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getSubCode()
    {
        return $this->getData( 'SubCode' );
    }

    /**
     * @param string $UnitOfMeasure
     * @return $this
     */
    public function setUnitOfMeasure($UnitOfMeasure)
    {
        $this->setData( 'UnitOfMeasure', $UnitOfMeasure );
        $this->UnitOfMeasure = $UnitOfMeasure;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getUnitOfMeasure()
    {
        return $this->getData( 'UnitOfMeasure' );
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

