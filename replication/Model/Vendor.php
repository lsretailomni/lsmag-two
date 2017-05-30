<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\VendorInterface;

class Vendor extends AbstractModel implements VendorInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_vendor';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Vendor' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

