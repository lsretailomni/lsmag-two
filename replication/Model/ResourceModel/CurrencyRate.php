<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CurrencyRate extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_currency_rate', 'currency_rate_id' );
    }


}

