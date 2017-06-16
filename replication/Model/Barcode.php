<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\BarcodeInterface;

class Barcode extends AbstractModel implements BarcodeInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_barcode';

    protected $_cacheTag = 'lsr_replication_barcode';

    protected $_eventPrefix = 'lsr_replication_barcode';

    protected $Blocked = null;

    protected $Del = null;

    protected $Description = null;

    protected $Id = null;

    protected $ItemId = null;

    protected $Uom = null;

    protected $VariantId = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Barcode' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @return $this
     */
    public function setBlocked($Blocked)
    {
        $this->setData( 'Blocked', $Blocked );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getBlocked()
    {
        return $this->Blocked;
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
    public function setDescription($Description)
    {
        $this->setData( 'Description', $Description );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDescription()
    {
        return $this->Description;
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
    public function setItemId($ItemId)
    {
        $this->setData( 'ItemId', $ItemId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getItemId()
    {
        return $this->ItemId;
    }

    /**
     * @return $this
     */
    public function setUom($Uom)
    {
        $this->setData( 'Uom', $Uom );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getUom()
    {
        return $this->Uom;
    }

    /**
     * @return $this
     */
    public function setVariantId($VariantId)
    {
        $this->setData( 'VariantId', $VariantId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVariantId()
    {
        return $this->VariantId;
    }


}

