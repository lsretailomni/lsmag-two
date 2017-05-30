<?php

namespace Ls\Replication\Model\ResourceModel\Price;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\Price', 'Ls\Replication\Model\ResourceModel\Price' );
    }


}

