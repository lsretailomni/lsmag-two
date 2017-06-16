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
    public function setItemCategoryID($ItemCategoryID)
    {
        $this->setData( 'ItemCategoryID', $ItemCategoryID );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getItemCategoryID()
    {
        return $this->ItemCategoryID;
    }


}

