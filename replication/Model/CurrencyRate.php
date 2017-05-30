<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\CurrencyRateInterface;

class CurrencyRate extends AbstractModel implements CurrencyRateInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_currency_rate';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\CurrencyRate' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

