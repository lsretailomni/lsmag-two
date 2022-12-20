<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplVendorInterface;

class ReplVendor extends AbstractModel implements ReplVendorInterface, IdentityInterface
{

    public const CACHE_TAG = 'ls_replication_repl_vendor';

    protected $_cacheTag = 'ls_replication_repl_vendor';

    protected $_eventPrefix = 'ls_replication_repl_vendor';

    /**
     * @property boolean $AllowCustomersToSelectPageSize
     */
    protected $AllowCustomersToSelectPageSize = null;

    /**
     * @property boolean $Blocked
     */
    protected $Blocked = null;

    /**
     * @property int $DisplayOrder
     */
    protected $DisplayOrder = null;

    /**
     * @property string $nav_id
     */
    protected $nav_id = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property int $ManufacturerTemplateId
     */
    protected $ManufacturerTemplateId = null;

    /**
     * @property string $Name
     */
    protected $Name = null;

    /**
     * @property int $PageSize
     */
    protected $PageSize = null;

    /**
     * @property string $PageSizeOptions
     */
    protected $PageSizeOptions = null;

    /**
     * @property int $PictureId
     */
    protected $PictureId = null;

    /**
     * @property boolean $Published
     */
    protected $Published = null;

    /**
     * @property string $UpdatedOnUtc
     */
    protected $UpdatedOnUtc = null;

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
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplVendor' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @param boolean $AllowCustomersToSelectPageSize
     * @return $this
     */
    public function setAllowCustomersToSelectPageSize($AllowCustomersToSelectPageSize)
    {
        $this->setData( 'AllowCustomersToSelectPageSize', $AllowCustomersToSelectPageSize );
        $this->AllowCustomersToSelectPageSize = $AllowCustomersToSelectPageSize;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAllowCustomersToSelectPageSize()
    {
        return $this->getData( 'AllowCustomersToSelectPageSize' );
    }

    /**
     * @param boolean $Blocked
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
     * @return boolean
     */
    public function getBlocked()
    {
        return $this->getData( 'Blocked' );
    }

    /**
     * @param int $DisplayOrder
     * @return $this
     */
    public function setDisplayOrder($DisplayOrder)
    {
        $this->setData( 'DisplayOrder', $DisplayOrder );
        $this->DisplayOrder = $DisplayOrder;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->getData( 'DisplayOrder' );
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
     * @param int $ManufacturerTemplateId
     * @return $this
     */
    public function setManufacturerTemplateId($ManufacturerTemplateId)
    {
        $this->setData( 'ManufacturerTemplateId', $ManufacturerTemplateId );
        $this->ManufacturerTemplateId = $ManufacturerTemplateId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getManufacturerTemplateId()
    {
        return $this->getData( 'ManufacturerTemplateId' );
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
     * @param int $PageSize
     * @return $this
     */
    public function setPageSize($PageSize)
    {
        $this->setData( 'PageSize', $PageSize );
        $this->PageSize = $PageSize;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->getData( 'PageSize' );
    }

    /**
     * @param string $PageSizeOptions
     * @return $this
     */
    public function setPageSizeOptions($PageSizeOptions)
    {
        $this->setData( 'PageSizeOptions', $PageSizeOptions );
        $this->PageSizeOptions = $PageSizeOptions;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getPageSizeOptions()
    {
        return $this->getData( 'PageSizeOptions' );
    }

    /**
     * @param int $PictureId
     * @return $this
     */
    public function setPictureId($PictureId)
    {
        $this->setData( 'PictureId', $PictureId );
        $this->PictureId = $PictureId;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return int
     */
    public function getPictureId()
    {
        return $this->getData( 'PictureId' );
    }

    /**
     * @param boolean $Published
     * @return $this
     */
    public function setPublished($Published)
    {
        $this->setData( 'Published', $Published );
        $this->Published = $Published;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return boolean
     */
    public function getPublished()
    {
        return $this->getData( 'Published' );
    }

    /**
     * @param string $UpdatedOnUtc
     * @return $this
     */
    public function setUpdatedOnUtc($UpdatedOnUtc)
    {
        $this->setData( 'UpdatedOnUtc', $UpdatedOnUtc );
        $this->UpdatedOnUtc = $UpdatedOnUtc;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedOnUtc()
    {
        return $this->getData( 'UpdatedOnUtc' );
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

