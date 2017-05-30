<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\VendorItemMappingInterface;

class VendorItemMapping extends AbstractModel implements VendorItemMappingInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_vendor_item_mapping';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\VendorItemMapping' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

