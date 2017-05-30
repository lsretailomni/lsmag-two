<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ExtendedVariantValue extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_extended_variant_value', 'extended_variant_value_id' );
    }


}

