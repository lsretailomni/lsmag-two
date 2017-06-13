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

    public function setDO($DO)
    {
        $this->DO = $DO;
        return $this;
    }

    public function getDO()
    {
        return $this->DO;
    }

    public function setDel($Del)
    {
        $this->Del = $Del;
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    public function setDeleted($Deleted)
    {
        $this->Deleted = $Deleted;
        return $this;
    }

    public function getDeleted()
    {
        return $this->Deleted;
    }

    public function setFP($FP)
    {
        $this->FP = $FP;
        return $this;
    }

    public function getFP()
    {
        return $this->FP;
    }

    public function setMId($MId)
    {
        $this->MId = $MId;
        return $this;
    }

    public function getMId()
    {
        return $this->MId;
    }

    public function setNId($NId)
    {
        $this->NId = $NId;
        return $this;
    }

    public function getNId()
    {
        return $this->NId;
    }


}

