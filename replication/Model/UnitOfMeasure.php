<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\UnitOfMeasureInterface;

class UnitOfMeasure extends AbstractModel implements UnitOfMeasureInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_unit_of_measure';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\UnitOfMeasure' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

