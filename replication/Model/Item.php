<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ItemInterface;

class Item extends AbstractModel implements ItemInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_item';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Item' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

