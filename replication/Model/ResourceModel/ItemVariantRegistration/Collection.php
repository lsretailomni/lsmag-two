<?php

namespace Ls\Replication\Model\ResourceModel\ItemVariantRegistration;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ItemVariantRegistration', 'Ls\Replication\Model\ResourceModel\ItemVariantRegistration' );
    }


}

