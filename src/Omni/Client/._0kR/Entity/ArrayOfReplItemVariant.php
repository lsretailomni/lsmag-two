<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfReplItemVariant implements IteratorAggregate
{
    /**
     * @property ReplItemVariant[] $ReplItemVariant
     */
    protected $ReplItemVariant = [
        
    ];

    /**
     * @param ReplItemVariant[] $ReplItemVariant
     * @return $this
     */
    public function setReplItemVariant($ReplItemVariant)
    {
        $this->ReplItemVariant = $ReplItemVariant;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ReplItemVariant );
    }

    /**
     * @return ReplItemVariant[]
     */
    public function getReplItemVariant()
    {
        return $this->ReplItemVariant;
    }
}

