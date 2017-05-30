<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Price extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_price', 'price_id' );
    }


}

