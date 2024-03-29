<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfMenuNode implements IteratorAggregate
{
    /**
     * @property MenuNode[] $MenuNode
     */
    protected $MenuNode = [
        
    ];

    /**
     * @param MenuNode[] $MenuNode
     * @return $this
     */
    public function setMenuNode($MenuNode)
    {
        $this->MenuNode = $MenuNode;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->MenuNode );
    }

    /**
     * @return MenuNode[]
     */
    public function getMenuNode()
    {
        return $this->MenuNode;
    }
}

