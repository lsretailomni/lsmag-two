<?php

namespace Ls\Replication\Model\ResourceModel\UnitOfMeasure;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\UnitOfMeasure', 'Ls\Replication\Model\ResourceModel\UnitOfMeasure' );
    }


}

