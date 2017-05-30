<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Store extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_store', 'store_id' );
    }


}

