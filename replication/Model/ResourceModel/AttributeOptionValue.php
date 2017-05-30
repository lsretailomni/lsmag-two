<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AttributeOptionValue extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_attribute_option_value', 'attribute_option_value_id' );
    }


}

