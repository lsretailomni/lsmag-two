<?php

namespace Ls\Replication\Model\ResourceModel\AttributeValue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\AttributeValue', 'Ls\Replication\Model\ResourceModel\AttributeValue' );
    }


}

