<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\AttributeOptionValueInterface;

class AttributeOptionValue extends AbstractModel implements AttributeOptionValueInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_attribute_option_value';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\AttributeOptionValue' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

