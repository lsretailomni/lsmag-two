<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfOrderDiscountLine implements IteratorAggregate
{
    /**
     * @property OrderDiscountLine[] $OrderDiscountLine
     */
    protected $OrderDiscountLine = [
        
    ];

    /**
     * @param OrderDiscountLine[] $OrderDiscountLine
     * @return $this
     */
    public function setOrderDiscountLine($OrderDiscountLine)
    {
        $this->OrderDiscountLine = $OrderDiscountLine;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->OrderDiscountLine );
    }

    /**
     * @return OrderDiscountLine[]
     */
    public function getOrderDiscountLine()
    {
        return $this->OrderDiscountLine;
    }
}

