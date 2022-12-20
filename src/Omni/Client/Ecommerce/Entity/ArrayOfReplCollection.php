<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfReplCollection implements IteratorAggregate
{

    /**
     * @property ReplCollection[] $ReplCollection
     */
    protected $ReplCollection = [
        
    ];

    /**
     * @param ReplCollection[] $ReplCollection
     * @return $this
     */
    public function setReplCollection($ReplCollection)
    {
        $this->ReplCollection = $ReplCollection;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ReplCollection );
    }

    /**
     * @return ReplCollection[]
     */
    public function getReplCollection()
    {
        return $this->ReplCollection;
    }


}

