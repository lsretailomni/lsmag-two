<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ImageInterface;

class Image extends AbstractModel implements ImageInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_image';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Image' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

