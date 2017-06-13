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

    public function setCC($CC)
    {
        $this->CC = $CC;
        return $this;
    }

    public function getCC()
    {
        return $this->CC;
    }

    public function setCF($CF)
    {
        $this->CF = $CF;
        return $this;
    }

    public function getCF()
    {
        return $this->CF;
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

    public function setSD($SD)
    {
        $this->SD = $SD;
        return $this;
    }

    public function getSD()
    {
        return $this->SD;
    }


}

