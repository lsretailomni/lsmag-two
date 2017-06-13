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

    public function setBlocked($Blocked)
    {
        $this->Blocked = $Blocked;
        return $this;
    }

    public function getBlocked()
    {
        return $this->Blocked;
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

    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    public function getDescription()
    {
        return $this->Description;
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

    public function setItemId($ItemId)
    {
        $this->ItemId = $ItemId;
        return $this;
    }

    public function getItemId()
    {
        return $this->ItemId;
    }

    public function setUom($Uom)
    {
        $this->Uom = $Uom;
        return $this;
    }

    public function getUom()
    {
        return $this->Uom;
    }

    public function setVariantId($VariantId)
    {
        $this->VariantId = $VariantId;
        return $this;
    }

    public function getVariantId()
    {
        return $this->VariantId;
    }


}

