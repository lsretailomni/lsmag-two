<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ItemCategoryInterface;

class ItemCategory extends AbstractModel implements ItemCategoryInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_item_category';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ItemCategory' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

