<?php

namespace Ls\Replication\Model\ResourceModel\ExtendedVariantValue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ExtendedVariantValue', 'Ls\Replication\Model\ResourceModel\ExtendedVariantValue' );
    }


}

