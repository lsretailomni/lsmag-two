<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AttributeValue extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_attribute_value', 'attribute_value_id' );
    }


}

