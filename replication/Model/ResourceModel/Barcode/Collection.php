<?php

namespace Ls\Replication\Model\ResourceModel\Barcode;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\Barcode', 'Ls\Replication\Model\ResourceModel\Barcode' );
    }


}

