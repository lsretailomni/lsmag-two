<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ItemCategory extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_item_category', 'item_category_id' );
    }


}

