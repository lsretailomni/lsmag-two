<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\BarcodeInterface;

class Barcode extends AbstractModel implements BarcodeInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_barcode';

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Barcode' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }


}

