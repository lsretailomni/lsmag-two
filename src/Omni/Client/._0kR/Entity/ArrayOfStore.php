<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfStore implements IteratorAggregate
{
    /**
     * @property Store[] $Store
     */
    protected $Store = [
        
    ];

    /**
     * @param Store[] $Store
     * @return $this
     */
    public function setStore($Store)
    {
        $this->Store = $Store;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->Store );
    }

    /**
     * @return Store[]
     */
    public function getStore()
    {
        return $this->Store;
    }
}

