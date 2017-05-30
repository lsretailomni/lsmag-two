<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\PriceInterface;

class Price extends AbstractModel implements PriceInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_price';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Price' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

