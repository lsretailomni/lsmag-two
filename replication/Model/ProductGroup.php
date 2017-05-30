<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ProductGroupInterface;

class ProductGroup extends AbstractModel implements ProductGroupInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_product_group';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ProductGroup' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

