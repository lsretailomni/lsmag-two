<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfHierarchy implements IteratorAggregate
{
    /**
     * @property Hierarchy[] $Hierarchy
     */
    protected $Hierarchy = [
        
    ];

    /**
     * @param Hierarchy[] $Hierarchy
     * @return $this
     */
    public function setHierarchy($Hierarchy)
    {
        $this->Hierarchy = $Hierarchy;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->Hierarchy );
    }

    /**
     * @return Hierarchy[]
     */
    public function getHierarchy()
    {
        return $this->Hierarchy;
    }
}

