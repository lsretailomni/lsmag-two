<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfHierarchyLeaf implements IteratorAggregate
{
    /**
     * @property HierarchyLeaf[] $HierarchyLeaf
     */
    protected $HierarchyLeaf = [
        
    ];

    /**
     * @param HierarchyLeaf[] $HierarchyLeaf
     * @return $this
     */
    public function setHierarchyLeaf($HierarchyLeaf)
    {
        $this->HierarchyLeaf = $HierarchyLeaf;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->HierarchyLeaf );
    }

    /**
     * @return HierarchyLeaf[]
     */
    public function getHierarchyLeaf()
    {
        return $this->HierarchyLeaf;
    }
}

