<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ExtendedVariantValueInterface;

class ExtendedVariantValue extends AbstractModel implements ExtendedVariantValueInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_extended_variant_value';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ExtendedVariantValue' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

