<?php

namespace Ls\Replication\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ImageLink extends AbstractDb
{

    public function _construct()
    {
        $this->_init( 'lsr_replication_image_link', 'image_link_id' );
    }


}

