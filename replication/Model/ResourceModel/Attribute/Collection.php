<?php

namespace Ls\Replication\Model\ResourceModel\Attribute;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\Attribute', 'Ls\Replication\Model\ResourceModel\Attribute' );
    }


}

