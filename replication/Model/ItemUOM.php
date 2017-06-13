<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ItemUOMInterface;

class ItemUOM extends AbstractModel implements ItemUOMInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_item_u_o_m';

    protected $_cacheTag = 'lsr_replication_item_u_o_m';

    protected $_eventPrefix = 'lsr_replication_item_u_o_m';

    protected $Del = null;

    protected $ItemId = null;

    protected $QtyPrUom = null;

    protected $StoreId = null;

    protected $UomCode = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ItemUOM' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
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

    public function setItemId($ItemId)
    {
        $this->ItemId = $ItemId;
        return $this;
    }

    public function getItemId()
    {
        return $this->ItemId;
    }

    public function setQtyPrUom($QtyPrUom)
    {
        $this->QtyPrUom = $QtyPrUom;
        return $this;
    }

    public function getQtyPrUom()
    {
        return $this->QtyPrUom;
    }

    public function setStoreId($StoreId)
    {
        $this->StoreId = $StoreId;
        return $this;
    }

    public function getStoreId()
    {
        return $this->StoreId;
    }

    public function setUomCode($UomCode)
    {
        $this->UomCode = $UomCode;
        return $this;
    }

    public function getUomCode()
    {
        return $this->UomCode;
    }


}

