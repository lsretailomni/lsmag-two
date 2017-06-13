<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\VendorInterface;

class Vendor extends AbstractModel implements VendorInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_vendor';

    protected $_cacheTag = 'lsr_replication_vendor';

    protected $_eventPrefix = 'lsr_replication_vendor';

    protected $ACTSPS = null;

    protected $COUtc = null;

    protected $DO = null;

    protected $Del = null;

    protected $Deleted = null;

    protected $Id = null;

    protected $MTId = null;

    protected $Name = null;

    protected $PId = null;

    protected $PS = null;

    protected $PSO = null;

    protected $Pub = null;

    protected $UOUtc = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Vendor' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    public function setACTSPS($ACTSPS)
    {
        $this->ACTSPS = $ACTSPS;
        return $this;
    }

    public function getACTSPS()
    {
        return $this->ACTSPS;
    }

    public function setCOUtc($COUtc)
    {
        $this->COUtc = $COUtc;
        return $this;
    }

    public function getCOUtc()
    {
        return $this->COUtc;
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

    public function setId($Id)
    {
        $this->Id = $Id;
        return $this;
    }

    public function getId()
    {
        return $this->Id;
    }

    public function setMTId($MTId)
    {
        $this->MTId = $MTId;
        return $this;
    }

    public function getMTId()
    {
        return $this->MTId;
    }

    public function setName($Name)
    {
        $this->Name = $Name;
        return $this;
    }

    public function getName()
    {
        return $this->Name;
    }

    public function setPId($PId)
    {
        $this->PId = $PId;
        return $this;
    }

    public function getPId()
    {
        return $this->PId;
    }

    public function setPS($PS)
    {
        $this->PS = $PS;
        return $this;
    }

    public function getPS()
    {
        return $this->PS;
    }

    public function setPSO($PSO)
    {
        $this->PSO = $PSO;
        return $this;
    }

    public function getPSO()
    {
        return $this->PSO;
    }

    public function setPub($Pub)
    {
        $this->Pub = $Pub;
        return $this;
    }

    public function getPub()
    {
        return $this->Pub;
    }

    public function setUOUtc($UOUtc)
    {
        $this->UOUtc = $UOUtc;
        return $this;
    }

    public function getUOUtc()
    {
        return $this->UOUtc;
    }


}

