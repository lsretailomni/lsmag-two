<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfReplHierarchyNode implements IteratorAggregate
{
    /**
     * @property ReplHierarchyNode[] $ReplHierarchyNode
     */
    protected $ReplHierarchyNode = [
        
    ];

    /**
     * @param ReplHierarchyNode[] $ReplHierarchyNode
     * @return $this
     */
    public function setReplHierarchyNode($ReplHierarchyNode)
    {
        $this->ReplHierarchyNode = $ReplHierarchyNode;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ReplHierarchyNode );
    }

    /**
     * @return ReplHierarchyNode[]
     */
    public function getReplHierarchyNode()
    {
        return $this->ReplHierarchyNode;
    }
}

