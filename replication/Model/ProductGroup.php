<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ProductGroupInterface;

class ProductGroup extends AbstractModel implements ProductGroupInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_product_group';

    protected $_cacheTag = 'lsr_replication_product_group';

    protected $_eventPrefix = 'lsr_replication_product_group';

    protected $Del = null;

    protected $Description = null;

    protected $Id = null;

    protected $ItemCategoryID = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ProductGroup' );
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

    public function setItemCategoryID($ItemCategoryID)
    {
        $this->ItemCategoryID = $ItemCategoryID;
        return $this;
    }

    public function getItemCategoryID()
    {
        return $this->ItemCategoryID;
    }


}

