<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfReplLoyVendorItemMapping implements IteratorAggregate
{
    /**
     * @property ReplLoyVendorItemMapping[] $ReplLoyVendorItemMapping
     */
    protected $ReplLoyVendorItemMapping = [
        
    ];

    /**
     * @param ReplLoyVendorItemMapping[] $ReplLoyVendorItemMapping
     * @return $this
     */
    public function setReplLoyVendorItemMapping($ReplLoyVendorItemMapping)
    {
        $this->ReplLoyVendorItemMapping = $ReplLoyVendorItemMapping;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ReplLoyVendorItemMapping );
    }

    /**
     * @return ReplLoyVendorItemMapping[]
     */
    public function getReplLoyVendorItemMapping()
    {
        return $this->ReplLoyVendorItemMapping;
    }
}

