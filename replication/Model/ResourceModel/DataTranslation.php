<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DataTranslation extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_data_translation', 'data_translation_id' );
    }


}

