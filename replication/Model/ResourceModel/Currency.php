<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Currency extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_currency', 'currency_id' );
    }


}

