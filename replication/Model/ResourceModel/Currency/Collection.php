<?php

namespace Ls\Replication\Model\ResourceModel\Currency;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\Currency', 'Ls\Replication\Model\ResourceModel\Currency' );
    }


}

