<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Attribute extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_attribute', 'attribute_id' );
    }


}

