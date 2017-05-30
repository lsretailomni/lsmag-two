<?php

namespace Ls\Replication\Model\ResourceModel\Image;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\Image', 'Ls\Replication\Model\ResourceModel\Image' );
    }


}

