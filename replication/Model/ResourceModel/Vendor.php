<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Vendor extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_vendor', 'vendor_id' );
    }


}

