<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class VendorItemMapping extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_vendor_item_mapping', 'vendor_item_mapping_id' );
    }


}

