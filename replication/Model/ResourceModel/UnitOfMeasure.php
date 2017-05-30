<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class UnitOfMeasure extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_unit_of_measure', 'unit_of_measure_id' );
    }


}

