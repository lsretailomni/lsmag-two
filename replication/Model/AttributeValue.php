<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\AttributeValueInterface;

class AttributeValue extends AbstractModel implements AttributeValueInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_attribute_value';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\AttributeValue' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

