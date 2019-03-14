<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\OrderQueueStatusFilterType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\OrderQueueType;
use Ls\Omni\Exception\InvalidEnumException;

class OrderSearchRequest
{

    /**
     * @property string $ContactId
     */
    protected $ContactId = null;

    /**
     * @property string $DateFrom
     */
    protected $DateFrom = null;

    /**
     * @property string $DateTo
     */
    protected $DateTo = null;

    /**
     * @property int $MaxOrders
     */
    protected $MaxOrders = null;

    /**
     * @property OrderQueueStatusFilterType $OrderStatusFilter
     */
    protected $OrderStatusFilter = null;

    /**
     * @property OrderQueueType $OrderType
     */
    protected $OrderType = null;

    /**
     * @property string $SearchKey
     */
    protected $SearchKey = null;

    /**
     * @property string $StoreId
     */
    protected $StoreId = null;

    /**
     * @param string $ContactId
     * @return $this
     */
    public function setContactId($ContactId)
    {
        $this->ContactId = $ContactId;
        return $this;
    }

    /**
     * @return string
     */
    public function getContactId()
    {
        return $this->ContactId;
    }

    /**
     * @param string $DateFrom
     * @return $this
     */
    public function setDateFrom($DateFrom)
    {
        $this->DateFrom = $DateFrom;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateFrom()
    {
        return $this->DateFrom;
    }

    /**
     * @param string $DateTo
     * @return $this
     */
    public function setDateTo($DateTo)
    {
        $this->DateTo = $DateTo;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateTo()
    {
        return $this->DateTo;
    }

    /**
     * @param int $MaxOrders
     * @return $this
     */
    public function setMaxOrders($MaxOrders)
    {
        $this->MaxOrders = $MaxOrders;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxOrders()
    {
        return $this->MaxOrders;
    }

    /**
     * @param OrderQueueStatusFilterType|string $OrderStatusFilter
     * @return $this
     * @throws InvalidEnumException
     */
    public function setOrderStatusFilter($OrderStatusFilter)
    {
        if ( ! $OrderStatusFilter instanceof OrderQueueStatusFilterType ) {
            if ( OrderQueueStatusFilterType::isValid( $OrderStatusFilter ) ) 
                $OrderStatusFilter = new OrderQueueStatusFilterType( $OrderStatusFilter );
            elseif ( OrderQueueStatusFilterType::isValidKey( $OrderStatusFilter ) ) 
                $OrderStatusFilter = new OrderQueueStatusFilterType( constant( "OrderQueueStatusFilterType::$OrderStatusFilter" ) );
            elseif ( ! $OrderStatusFilter instanceof OrderQueueStatusFilterType )
                throw new InvalidEnumException();
        }
        $this->OrderStatusFilter = $OrderStatusFilter->getValue();

        return $this;
    }

    /**
     * @return OrderQueueStatusFilterType
     */
    public function getOrderStatusFilter()
    {
        return $this->OrderStatusFilter;
    }

    /**
     * @param OrderQueueType|string $OrderType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setOrderType($OrderType)
    {
        if ( ! $OrderType instanceof OrderQueueType ) {
            if ( OrderQueueType::isValid( $OrderType ) ) 
                $OrderType = new OrderQueueType( $OrderType );
            elseif ( OrderQueueType::isValidKey( $OrderType ) ) 
                $OrderType = new OrderQueueType( constant( "OrderQueueType::$OrderType" ) );
            elseif ( ! $OrderType instanceof OrderQueueType )
                throw new InvalidEnumException();
        }
        $this->OrderType = $OrderType->getValue();

        return $this;
    }

    /**
     * @return OrderQueueType
     */
    public function getOrderType()
    {
        return $this->OrderType;
    }

    /**
     * @param string $SearchKey
     * @return $this
     */
    public function setSearchKey($SearchKey)
    {
        $this->SearchKey = $SearchKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getSearchKey()
    {
        return $this->SearchKey;
    }

    /**
     * @param string $StoreId
     * @return $this
     */
    public function setStoreId($StoreId)
    {
        $this->StoreId = $StoreId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->StoreId;
    }


}

