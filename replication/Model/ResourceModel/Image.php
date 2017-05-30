<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Image extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_image', 'image_id' );
    }


}

