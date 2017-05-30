<?php

namespace Ls\Replication\Model\ResourceModel\ItemCategory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ItemCategory', 'Ls\Replication\Model\ResourceModel\ItemCategory' );
    }


}

