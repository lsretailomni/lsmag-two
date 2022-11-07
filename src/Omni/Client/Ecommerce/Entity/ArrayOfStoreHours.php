<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfStoreHours implements IteratorAggregate
{

    /**
     * @property StoreHours[] $StoreHours
     */
    protected $StoreHours = [
        
    ];

    /**
     * @param StoreHours[] $StoreHours
     * @return $this
     */
    public function setStoreHours($StoreHours)
    {
        $this->StoreHours = $StoreHours;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->StoreHours );
    }

    /**
     * @return StoreHours[]
     */
    public function getStoreHours()
    {
        return $this->StoreHours;
    }


}

