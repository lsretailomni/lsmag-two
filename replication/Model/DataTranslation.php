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
    public function setKey($Key)
    {
        $this->setData( 'Key', $Key );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getKey()
    {
        return $this->Key;
    }

    /**
     * @return $this
     */
    public function setLC($LC)
    {
        $this->setData( 'LC', $LC );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getLC()
    {
        return $this->LC;
    }

    /**
     * @return $this
     */
    public function setTId($TId)
    {
        $this->setData( 'TId', $TId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getTId()
    {
        return $this->TId;
    }

    /**
     * @return $this
     */
    public function setTx($Tx)
    {
        $this->setData( 'Tx', $Tx );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getTx()
    {
        return $this->Tx;
    }


}

