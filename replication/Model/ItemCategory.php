<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ItemCategoryInterface;

class ItemCategory extends AbstractModel implements ItemCategoryInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_item_category';

    protected $_cacheTag = 'lsr_replication_item_category';

    protected $_eventPrefix = 'lsr_replication_item_category';

    protected $Del = null;

    protected $Description = null;

    protected $Id = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ItemCategory' );
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


}

