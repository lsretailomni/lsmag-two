<?php

namespace Ls\Replication\Model\ResourceModel\DataTranslation;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\DataTranslation', 'Ls\Replication\Model\ResourceModel\DataTranslation' );
    }


}

