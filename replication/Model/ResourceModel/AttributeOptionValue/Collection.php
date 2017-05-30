<?php

namespace Ls\Replication\Model\ResourceModel\AttributeOptionValue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\AttributeOptionValue', 'Ls\Replication\Model\ResourceModel\AttributeOptionValue' );
    }


}

