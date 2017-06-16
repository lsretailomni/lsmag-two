<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\CurrencyRateInterface;

class CurrencyRate extends AbstractModel implements CurrencyRateInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_currency_rate';

    protected $_cacheTag = 'lsr_replication_currency_rate';

    protected $_eventPrefix = 'lsr_replication_currency_rate';

    protected $CC = null;

    protected $CF = null;

    protected $Del = null;

    protected $SD = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\CurrencyRate' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @return $this
     */
    public function setCC($CC)
    {
        $this->setData( 'CC', $CC );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCC()
    {
        return $this->CC;
    }

    /**
     * @return $this
     */
    public function setCF($CF)
    {
        $this->setData( 'CF', $CF );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCF()
    {
        return $this->CF;
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
    public function setSD($SD)
    {
        $this->setData( 'SD', $SD );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getSD()
    {
        return $this->SD;
    }


}

