<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ImageInterface;

class Image extends AbstractModel implements ImageInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_image';

    protected $_cacheTag = 'lsr_replication_image';

    protected $_eventPrefix = 'lsr_replication_image';

    protected $Del = null;

    protected $Id = null;

    protected $Image64 = null;

    protected $Location = null;

    protected $LocationType = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\Image' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    public function setDel($Del)
    {
        $this->Del = $Del;
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    public function setId($Id)
    {
        $this->Id = $Id;
        return $this;
    }

    public function getId()
    {
        return $this->Id;
    }

    public function setImage64($Image64)
    {
        $this->Image64 = $Image64;
        return $this;
    }

    public function getImage64()
    {
        return $this->Image64;
    }

    public function setLocation($Location)
    {
        $this->Location = $Location;
        return $this;
    }

    public function getLocation()
    {
        return $this->Location;
    }

    public function setLocationType($LocationType)
    {
        $this->LocationType = $LocationType;
        return $this;
    }

    public function getLocationType()
    {
        return $this->LocationType;
    }


}

