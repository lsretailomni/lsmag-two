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

    /**
     * @return $this
     */
    public function setACTSPS($ACTSPS)
    {
        $this->setData( 'ACTSPS', $ACTSPS );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getACTSPS()
    {
        return $this->ACTSPS;
    }

    /**
     * @return $this
     */
    public function setCOUtc($COUtc)
    {
        $this->setData( 'COUtc', $COUtc );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCOUtc()
    {
        return $this->COUtc;
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
    public function setId($Id)
    {
        $this->setData( 'Id', $Id );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getId()
    {
        return $this->Id;
    }

    /**
     * @return $this
     */
    public function setMTId($MTId)
    {
        $this->setData( 'MTId', $MTId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getMTId()
    {
        return $this->MTId;
    }

    /**
     * @return $this
     */
    public function setName($Name)
    {
        $this->setData( 'Name', $Name );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getName()
    {
        return $this->Name;
    }

    /**
     * @return $this
     */
    public function setPId($PId)
    {
        $this->setData( 'PId', $PId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getPId()
    {
        return $this->PId;
    }

    /**
     * @return $this
     */
    public function setPS($PS)
    {
        $this->setData( 'PS', $PS );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getPS()
    {
        return $this->PS;
    }

    /**
     * @return $this
     */
    public function setPSO($PSO)
    {
        $this->setData( 'PSO', $PSO );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getPSO()
    {
        return $this->PSO;
    }

    /**
     * @return $this
     */
    public function setPub($Pub)
    {
        $this->setData( 'Pub', $Pub );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getPub()
    {
        return $this->Pub;
    }

    /**
     * @return $this
     */
    public function setUOUtc($UOUtc)
    {
        $this->setData( 'UOUtc', $UOUtc );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getUOUtc()
    {
        return $this->UOUtc;
    }


}

