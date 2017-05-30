<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Item extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_item', 'item_id' );
    }


}

