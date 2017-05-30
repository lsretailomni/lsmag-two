<?php

namespace Ls\Replication\Model\ResourceModel\CurrencyRate;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\CurrencyRate', 'Ls\Replication\Model\ResourceModel\CurrencyRate' );
    }


}

