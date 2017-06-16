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

    /**
     * @return $this
     */
    public function setDel($Del)
    {
        $this->setData( 'Del', $Del );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    /**
     * @return $this
     */
    public function setId($Id)
    {
        $this->setData( 'Id', $Id );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getId()
    {
        return $this->Id;
    }

    /**
     * @return $this
     */
    public function setImage64($Image64)
    {
        $this->setData( 'Image64', $Image64 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getImage64()
    {
        return $this->Image64;
    }

    /**
     * @return $this
     */
    public function setLocation($Location)
    {
        $this->setData( 'Location', $Location );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getLocation()
    {
        return $this->Location;
    }

    /**
     * @return $this
     */
    public function setLocationType($LocationType)
    {
        $this->setData( 'LocationType', $LocationType );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getLocationType()
    {
        return $this->LocationType;
    }


}

