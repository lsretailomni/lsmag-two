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
    public function setQtyPrUom($QtyPrUom)
    {
        $this->setData( 'QtyPrUom', $QtyPrUom );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getQtyPrUom()
    {
        return $this->QtyPrUom;
    }

    /**
     * @return $this
     */
    public function setStoreId($StoreId)
    {
        $this->setData( 'StoreId', $StoreId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getStoreId()
    {
        return $this->StoreId;
    }

    /**
     * @return $this
     */
    public function setUomCode($UomCode)
    {
        $this->setData( 'UomCode', $UomCode );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getUomCode()
    {
        return $this->UomCode;
    }


}

