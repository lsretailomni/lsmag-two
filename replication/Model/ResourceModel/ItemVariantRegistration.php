<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ItemVariantRegistration extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_item_variant_registration', 'item_variant_registration_id' );
    }


}

