<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\VendorItemMappingInterface;

class VendorItemMapping extends AbstractModel implements VendorItemMappingInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_vendor_item_mapping';

    protected $_cacheTag = 'lsr_replication_vendor_item_mapping';

    protected $_eventPrefix = 'lsr_replication_vendor_item_mapping';

    protected $DO = null;

    protected $Del = null;

    protected $Deleted = null;

    protected $FP = null;

    protected $MId = null;

    protected $NId = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\VendorItemMapping' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @return $this
     */
    public function setDO($DO)
    {
        $this->setData( 'DO', $DO );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDO()
    {
        return $this->DO;
    }

    /**
     * @return $this
     */
    public function setDel($Del)
    {
        $this->setData( 'Del', $Del );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    /**
     * @return $this
     */
    public function setDeleted($Deleted)
    {
        $this->setData( 'Deleted', $Deleted );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDeleted()
    {
        return $this->Deleted;
    }

    /**
     * @return $this
     */
    public function setFP($FP)
    {
        $this->setData( 'FP', $FP );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getFP()
    {
        return $this->FP;
    }

    /**
     * @return $this
     */
    public function setMId($MId)
    {
        $this->setData( 'MId', $MId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getMId()
    {
        return $this->MId;
    }

    /**
     * @return $this
     */
    public function setNId($NId)
    {
        $this->setData( 'NId', $NId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getNId()
    {
        return $this->NId;
    }


}

