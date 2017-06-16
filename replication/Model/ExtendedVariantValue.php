<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ExtendedVariantValueInterface;

class ExtendedVariantValue extends AbstractModel implements ExtendedVariantValueInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_extended_variant_value';

    protected $_cacheTag = 'lsr_replication_extended_variant_value';

    protected $_eventPrefix = 'lsr_replication_extended_variant_value';

    protected $Code = null;

    protected $Del = null;

    protected $Dimensions = null;

    protected $FrameworkCode = null;

    protected $ItemId = null;

    protected $Order = null;

    protected $Timestamp = null;

    protected $Value = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ExtendedVariantValue' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @return $this
     */
    public function setCode($Code)
    {
        $this->setData( 'Code', $Code );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getCode()
    {
        return $this->Code;
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
    public function setDimensions($Dimensions)
    {
        $this->setData( 'Dimensions', $Dimensions );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDimensions()
    {
        return $this->Dimensions;
    }

    /**
     * @return $this
     */
    public function setFrameworkCode($FrameworkCode)
    {
        $this->setData( 'FrameworkCode', $FrameworkCode );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getFrameworkCode()
    {
        return $this->FrameworkCode;
    }

    /**
     * @return $this
     */
    public function setItemId($ItemId)
    {
        $this->setData( 'ItemId', $ItemId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getItemId()
    {
        return $this->ItemId;
    }

    /**
     * @return $this
     */
    public function setOrder($Order)
    {
        $this->setData( 'Order', $Order );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * @return $this
     */
    public function setTimestamp($Timestamp)
    {
        $this->setData( 'Timestamp', $Timestamp );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getTimestamp()
    {
        return $this->Timestamp;
    }

    /**
     * @return $this
     */
    public function setValue($Value)
    {
        $this->setData( 'Value', $Value );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getValue()
    {
        return $this->Value;
    }


}

