<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplItemRecipeInterface;

class ReplItemRecipe extends AbstractModel implements ReplItemRecipeInterface, IdentityInterface
{
    public const CACHE_TAG = 'ls_replication_repl_item_recipe';

    protected $_cacheTag = 'ls_replication_repl_item_recipe';

    protected $_eventPrefix = 'ls_replication_repl_item_recipe';

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property boolean $Exclusion
     */
    protected $Exclusion = null;

    /**
     * @property float $ExclusionPrice
     */
    protected $ExclusionPrice = null;

    /**
     * @property string $ImageId
     */
    protected $ImageId = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property string $ItemNo
     */
    protected $ItemNo = null;

    /**
     * @property int $LineNo
     */
    protected $LineNo = null;

    /**
     * @property float $QuantityPer
     */
    protected $QuantityPer = null;

    /**
     * @property string $RecipeNo
     */
    protected $RecipeNo = null;

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
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplItemRecipe' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
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
     * @param boolean $Exclusion
     * @return $this
     */
    public function setExclusion($Exclusion)
    {
        $this->setData( 'Exclusion', $Exclusion );
        $this->Exclusion = $Exclusion;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getExclusion()
    {
        return $this->getData( 'Exclusion' );
    }

    /**
     * @param float $ExclusionPrice
     * @return $this
     */
    public function setExclusionPrice($ExclusionPrice)
    {
        $this->setData( 'ExclusionPrice', $ExclusionPrice );
        $this->ExclusionPrice = $ExclusionPrice;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getExclusionPrice()
    {
        return $this->getData( 'ExclusionPrice' );
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
     * @param int $LineNo
     * @return $this
     */
    public function setLineNo($LineNo)
    {
        $this->setData( 'LineNo', $LineNo );
        $this->LineNo = $LineNo;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getLineNo()
    {
        return $this->getData( 'LineNo' );
    }

    /**
     * @param float $QuantityPer
     * @return $this
     */
    public function setQuantityPer($QuantityPer)
    {
        $this->setData( 'QuantityPer', $QuantityPer );
        $this->QuantityPer = $QuantityPer;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getQuantityPer()
    {
        return $this->getData( 'QuantityPer' );
    }

    /**
     * @param string $RecipeNo
     * @return $this
     */
    public function setRecipeNo($RecipeNo)
    {
        $this->setData( 'RecipeNo', $RecipeNo );
        $this->RecipeNo = $RecipeNo;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getRecipeNo()
    {
        return $this->getData( 'RecipeNo' );
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

