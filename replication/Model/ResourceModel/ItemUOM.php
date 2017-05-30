<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ItemUOM extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_item_u_o_m', 'item_u_o_m_id' );
    }


}

