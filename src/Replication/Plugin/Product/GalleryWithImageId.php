<?php

namespace Ls\Replication\Plugin\Product;

use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\DB\Select;

class GalleryWithImageId
{
    public function afterCreateBatchBaseSelect(
        Gallery $subject,
        Select $select
    ) {
        $select->columns('image_id');
        return $select;
    }
}
