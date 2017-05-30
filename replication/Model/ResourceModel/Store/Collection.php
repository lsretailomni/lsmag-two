<?php

namespace Ls\Replication\Model\ResourceModel\Store;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\Store', 'Ls\Replication\Model\ResourceModel\Store' );
    }


}

