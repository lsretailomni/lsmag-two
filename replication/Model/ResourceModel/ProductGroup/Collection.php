<?php

namespace Ls\Replication\Model\ResourceModel\ProductGroup;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ProductGroup', 'Ls\Replication\Model\ResourceModel\ProductGroup' );
    }


}

