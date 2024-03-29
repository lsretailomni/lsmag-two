<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfCustomerEntry implements IteratorAggregate
{
    /**
     * @property CustomerEntry[] $CustomerEntry
     */
    protected $CustomerEntry = [
        
    ];

    /**
     * @param CustomerEntry[] $CustomerEntry
     * @return $this
     */
    public function setCustomerEntry($CustomerEntry)
    {
        $this->CustomerEntry = $CustomerEntry;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->CustomerEntry );
    }

    /**
     * @return CustomerEntry[]
     */
    public function getCustomerEntry()
    {
        return $this->CustomerEntry;
    }
}

