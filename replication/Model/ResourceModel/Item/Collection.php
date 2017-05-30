<?php

namespace Ls\Replication\Model\ResourceModel\Item;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\Item', 'Ls\Replication\Model\ResourceModel\Item' );
    }


}

