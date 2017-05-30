<?php

namespace Ls\Replication\Model\ResourceModel\VendorItemMapping;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\VendorItemMapping', 'Ls\Replication\Model\ResourceModel\VendorItemMapping' );
    }


}

