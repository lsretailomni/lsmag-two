<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ItemVariantRegistrationInterface;

class ItemVariantRegistration extends AbstractModel implements ItemVariantRegistrationInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_item_variant_registration';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ItemVariantRegistration' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

