<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfOrderHospSubLine implements IteratorAggregate
{

    /**
     * @property OrderHospSubLine[] $OrderHospSubLine
     */
    protected $OrderHospSubLine = [
        
    ];

    /**
     * @param OrderHospSubLine[] $OrderHospSubLine
     * @return $this
     */
    public function setOrderHospSubLine($OrderHospSubLine)
    {
        $this->OrderHospSubLine = $OrderHospSubLine;
        return $this;
    }

    /**
     * @return OrderHospSubLine[]
     */
    public function getIterator()
    {
        return new ArrayIterator( $this->OrderHospSubLine );
    }

    /**
     * @return OrderHospSubLine[]
     */
    public function getOrderHospSubLine()
    {
        return $this->OrderHospSubLine;
    }


}

