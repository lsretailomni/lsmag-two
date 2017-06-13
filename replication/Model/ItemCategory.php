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


}

