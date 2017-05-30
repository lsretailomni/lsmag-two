<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\StoreInterface;

class Store extends AbstractModel implements StoreInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_store';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Store' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

