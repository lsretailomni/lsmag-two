<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ImageLinkInterface;

class ImageLink extends AbstractModel implements ImageLinkInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_image_link';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ImageLink' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

