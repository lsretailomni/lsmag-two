<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\AttributeInterface;

class Attribute extends AbstractModel implements AttributeInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_attribute';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Attribute' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

