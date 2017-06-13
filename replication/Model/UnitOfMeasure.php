<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\UnitOfMeasureInterface;

class UnitOfMeasure extends AbstractModel implements UnitOfMeasureInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_unit_of_measure';

    protected $_cacheTag = 'lsr_replication_unit_of_measure';

    protected $_eventPrefix = 'lsr_replication_unit_of_measure';

    protected $Del = null;

    protected $Description = null;

    protected $Id = null;

    protected $ShortDescription = null;

    protected $UnitDecimals = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\UnitOfMeasure' );
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

    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    public function getDescription()
    {
        return $this->Description;
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

    public function setShortDescription($ShortDescription)
    {
        $this->ShortDescription = $ShortDescription;
        return $this;
    }

    public function getShortDescription()
    {
        return $this->ShortDescription;
    }

    public function setUnitDecimals($UnitDecimals)
    {
        $this->UnitDecimals = $UnitDecimals;
        return $this;
    }

    public function getUnitDecimals()
    {
        return $this->UnitDecimals;
    }


}

