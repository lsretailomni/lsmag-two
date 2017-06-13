<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\DataTranslationInterface;

class DataTranslation extends AbstractModel implements DataTranslationInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_data_translation';

    protected $_cacheTag = 'lsr_replication_data_translation';

    protected $_eventPrefix = 'lsr_replication_data_translation';

    protected $Del = null;

    protected $Key = null;

    protected $LC = null;

    protected $TId = null;

    protected $Tx = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\DataTranslation' );
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

    public function setKey($Key)
    {
        $this->Key = $Key;
        return $this;
    }

    public function getKey()
    {
        return $this->Key;
    }

    public function setLC($LC)
    {
        $this->LC = $LC;
        return $this;
    }

    public function getLC()
    {
        return $this->LC;
    }

    public function setTId($TId)
    {
        $this->TId = $TId;
        return $this;
    }

    public function getTId()
    {
        return $this->TId;
    }

    public function setTx($Tx)
    {
        $this->Tx = $Tx;
        return $this;
    }

    public function getTx()
    {
        return $this->Tx;
    }


}

