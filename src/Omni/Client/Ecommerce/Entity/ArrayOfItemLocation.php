<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfItemLocation implements IteratorAggregate
{

    /**
     * @property ItemLocation[] $ItemLocation
     */
    protected $ItemLocation = [
        
    ];

    /**
     * @param ItemLocation[] $ItemLocation
     * @return $this
     */
    public function setItemLocation($ItemLocation)
    {
        $this->ItemLocation = $ItemLocation;
        return $this;
    }

    /**
     * @return ItemLocation[]
     */
    public function getIterator()
    {
        return new ArrayIterator( $this->ItemLocation );
    }

    /**
     * @return ItemLocation[]
     */
    public function getItemLocation()
    {
        return $this->ItemLocation;
    }


}
