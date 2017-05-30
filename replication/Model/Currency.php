<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\CurrencyInterface;

class Currency extends AbstractModel implements CurrencyInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_currency';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Currency' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

