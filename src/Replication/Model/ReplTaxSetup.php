<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ReplTaxSetupInterface;

class ReplTaxSetup extends AbstractModel implements ReplTaxSetupInterface, IdentityInterface
{

    const CACHE_TAG = 'ls_replication_repl_tax_setup';

    protected $_cacheTag = 'ls_replication_repl_tax_setup';

    protected $_eventPrefix = 'ls_replication_repl_tax_setup';

    /**
     * @property string $BusinessTaxGroup
     */
    protected $BusinessTaxGroup = null;

    /**
     * @property boolean $IsDeleted
     */
    protected $IsDeleted = null;

    /**
     * @property string $ProductTaxGroup
     */
    protected $ProductTaxGroup = null;

    /**
     * @property float $TaxPercent
     */
    protected $TaxPercent = null;

    /**
     * @property string $scope
     */
    protected $scope = null;

    /**
     * @property int $scope_id
     */
    protected $scope_id = null;

    /**
     * @property string $processed
     */
    protected $processed = null;

    /**
     * @property string $is_updated
     */
    protected $is_updated = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ReplTaxSetup' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @param string $BusinessTaxGroup
     * @return $this
     */
    public function setBusinessTaxGroup($BusinessTaxGroup)
    {
        $this->setData( 'BusinessTaxGroup', $BusinessTaxGroup );
        $this->BusinessTaxGroup = $BusinessTaxGroup;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getBusinessTaxGroup()
    {
        return $this->getData( 'BusinessTaxGroup' );
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
     * @param string $ProductTaxGroup
     * @return $this
     */
    public function setProductTaxGroup($ProductTaxGroup)
    {
        $this->setData( 'ProductTaxGroup', $ProductTaxGroup );
        $this->ProductTaxGroup = $ProductTaxGroup;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return string
     */
    public function getProductTaxGroup()
    {
        return $this->getData( 'ProductTaxGroup' );
    }

    /**
     * @param float $TaxPercent
     * @return $this
     */
    public function setTaxPercent($TaxPercent)
    {
        $this->setData( 'TaxPercent', $TaxPercent );
        $this->TaxPercent = $TaxPercent;
        $this->setDataChanges( TRUE );
        return $this;
    }

    /**
     * @return float
     */
    public function getTaxPercent()
    {
        return $this->getData( 'TaxPercent' );
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
     * @param string $processed
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
     * @return string
     */
    public function getProcessed()
    {
        return $this->getData( 'processed' );
    }

    /**
     * @param string $is_updated
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
     * @return string
     */
    public function getIsUpdated()
    {
        return $this->getData( 'is_updated' );
    }


}

