<?php

namespace Ls\Replication\Model\ResourceModel\Vendor;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\Vendor', 'Ls\Replication\Model\ResourceModel\Vendor' );
    }


}

