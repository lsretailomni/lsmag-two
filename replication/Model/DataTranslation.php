<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\DataTranslationInterface;

class DataTranslation extends AbstractModel implements DataTranslationInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_data_translation';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\DataTranslation' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

