<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfSalesEntry implements IteratorAggregate
{
    /**
     * @property SalesEntry[] $SalesEntry
     */
    protected $SalesEntry = [
        
    ];

    /**
     * @param SalesEntry[] $SalesEntry
     * @return $this
     */
    public function setSalesEntry($SalesEntry)
    {
        $this->SalesEntry = $SalesEntry;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->SalesEntry );
    }

    /**
     * @return SalesEntry[]
     */
    public function getSalesEntry()
    {
        return $this->SalesEntry;
    }
}
