<?php

namespace Ls\Replication\Model\ResourceModel\ImageLink;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ImageLink', 'Ls\Replication\Model\ResourceModel\ImageLink' );
    }


}

