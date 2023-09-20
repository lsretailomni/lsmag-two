<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfOrderLineStatus implements IteratorAggregate
{
    /**
     * @property OrderLineStatus[] $OrderLineStatus
     */
    protected $OrderLineStatus = [
        
    ];

    /**
     * @param OrderLineStatus[] $OrderLineStatus
     * @return $this
     */
    public function setOrderLineStatus($OrderLineStatus)
    {
        $this->OrderLineStatus = $OrderLineStatus;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->OrderLineStatus );
    }

    /**
     * @return OrderLineStatus[]
     */
    public function getOrderLineStatus()
    {
        return $this->OrderLineStatus;
    }
}
