<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfReplCustomer implements IteratorAggregate
{
    /**
     * @property ReplCustomer[] $ReplCustomer
     */
    protected $ReplCustomer = [
        
    ];

    /**
     * @param ReplCustomer[] $ReplCustomer
     * @return $this
     */
    public function setReplCustomer($ReplCustomer)
    {
        $this->ReplCustomer = $ReplCustomer;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ReplCustomer );
    }

    /**
     * @return ReplCustomer[]
     */
    public function getReplCustomer()
    {
        return $this->ReplCustomer;
    }
}

