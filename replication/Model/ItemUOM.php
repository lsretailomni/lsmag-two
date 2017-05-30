<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ItemUOMInterface;

class ItemUOM extends AbstractModel implements ItemUOMInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_item_u_o_m';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ItemUOM' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

